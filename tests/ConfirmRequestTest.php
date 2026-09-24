<?php

namespace Witify\Support\Tests;

use Illuminate\Routing\Router;
use Witify\Support\Http\Confirmation\Actions\ConfirmRequestAction;
use Witify\Support\Http\Middlewares\ClearConfirmationFlow;
use Witify\Support\Tests\Fixtures\OrderController;

class ConfirmRequestTest extends TestCase
{
    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware(['web', ClearConfirmationFlow::class])->group(function (Router $router): void {
            $router->delete('/orders/1', [OrderController::class, 'destroy']);
            $router->post('/orders/1/close', [OrderController::class, 'close']);
            $router->get('/ping', [OrderController::class, 'ping']);
        });
    }

    public function test_the_first_call_answers_428_with_the_confirmation_to_show(): void
    {
        $this->actingAs($this->user());

        $response = $this->deleteJson('/orders/1')->assertStatus(428);

        $response->assertJsonPath('code', 'confirmation_required');
        $response->assertJsonPath('message', 'Confirmation required');
        $response->assertJsonPath('confirmation.title', 'Delete the order?');
        $response->assertJsonPath('confirmation.message', '<p>This cannot be undone.</p>');
        $response->assertJsonPath('confirmation.confirmText', 'Yes');
        $response->assertJsonPath('confirmation.cancelText', 'Cancel');
        $response->assertJsonPath('confirmation.requiresPassword', false);
        $this->assertNotEmpty($response->json('confirmation.flow'));
        $this->assertNotEmpty($response->json('confirmation.token'));
    }

    public function test_the_replay_with_the_flow_and_token_headers_runs_the_action_and_clears_the_flow(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $first = $this->deleteJson('/orders/1')->assertStatus(428);

        $this->deleteJson('/orders/1', [], [
            'X-Confirmation-Flow' => $first->json('confirmation.flow'),
            'X-Confirmation-Token' => $first->json('confirmation.token'),
        ])->assertOk()->assertJson(['deleted' => true, 'user' => $user->id]);

        $this->assertSame([], session(ConfirmRequestAction::SESSION_KEY));
    }

    public function test_a_wrong_token_or_a_different_payload_asks_again(): void
    {
        $this->actingAs($this->user());

        $first = $this->deleteJson('/orders/1')->assertStatus(428);

        $this->deleteJson('/orders/1', [], [
            'X-Confirmation-Flow' => $first->json('confirmation.flow'),
            'X-Confirmation-Token' => 'not-the-token',
        ])->assertStatus(428);

        $this->deleteJson('/orders/1', ['reason' => 'changed'], [
            'X-Confirmation-Flow' => $first->json('confirmation.flow'),
            'X-Confirmation-Token' => $first->json('confirmation.token'),
        ])->assertStatus(428);
    }

    public function test_the_password_confirmation_checks_the_password_and_counts_the_attempts(): void
    {
        $this->actingAs($this->user('Ada', 'secret'));

        $first = $this->postJson('/orders/1/close')->assertStatus(428);
        $first->assertJsonPath('confirmation.requiresPassword', true);
        $first->assertJsonPath('confirmation.title', 'Password confirmation');
        $first->assertJsonPath('confirmation.message', 'Please confirm your password before continuing.');

        $headers = [
            'X-Confirmation-Flow' => $first->json('confirmation.flow'),
            'X-Confirmation-Token' => $first->json('confirmation.token'),
        ];

        // The attempts left are counted before the failed attempt is recorded,
        // as the applications always did: three tries show 3, then 2, then 1.
        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'wrong'])
            ->assertStatus(428)
            ->assertJsonPath('confirmation.errorMessage', 'The provided password is incorrect. 3 attempts left.');

        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'wrong'])
            ->assertStatus(428)
            ->assertJsonPath('confirmation.errorMessage', 'The provided password is incorrect. 2 attempts left.');

        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'secret'])
            ->assertOk()
            ->assertJson(['closed' => true, 'password' => 'secret']);
    }

    public function test_a_right_password_wipes_the_count_of_wrong_ones(): void
    {
        $this->actingAs($this->user('Ada', 'secret'));

        $headers = $this->confirmationHeaders();

        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'wrong'])->assertStatus(428);
        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'wrong'])->assertStatus(428);
        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'secret'])->assertOk();

        $this->postJson('/orders/1/close', [], $this->confirmationHeaders() + ['X-Confirmation-Password' => 'wrong'])
            ->assertStatus(428)
            ->assertJsonPath('confirmation.errorMessage', 'The provided password is incorrect. 3 attempts left.');
    }

    public function test_one_users_wrong_passwords_do_not_lock_another_user_out(): void
    {
        $locked = $this->user('Ada', 'secret');
        $other = $this->user('Bob', 'secret');

        $this->actingAs($locked);
        $headers = $this->confirmationHeaders();

        foreach (range(1, 3) as $attempt) {
            $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'wrong'])->assertStatus(428);
        }

        $this->postJson('/orders/1/close', [], $headers + ['X-Confirmation-Password' => 'secret'])->assertStatus(429);

        $this->flushSession();
        $this->actingAs($other);

        $this->postJson('/orders/1/close', [], $this->confirmationHeaders() + ['X-Confirmation-Password' => 'secret'])->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function confirmationHeaders(): array
    {
        $first = $this->postJson('/orders/1/close')->assertStatus(428);

        return [
            'X-Confirmation-Flow' => (string) $first->json('confirmation.flow'),
            'X-Confirmation-Token' => (string) $first->json('confirmation.token'),
        ];
    }

    public function test_the_rate_limit_answers_429_with_the_package_message(): void
    {
        $this->actingAs($this->user());

        $this->getJson('/ping')->assertOk();
        $this->getJson('/ping')->assertOk();
        $this->getJson('/ping')->assertStatus(429)->assertJson([
            'status' => 'error',
            'message' => 'Too many requests. Please try again later.',
        ]);
    }
}
