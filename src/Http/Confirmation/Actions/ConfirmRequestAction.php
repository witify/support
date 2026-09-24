<?php

namespace Witify\Support\Http\Confirmation\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Witify\Support\Action\Action;
use Witify\Support\Exception\RateLimitException;
use Witify\Support\Http\Confirmation\ConfirmationHttpException;

class ConfirmRequestAction implements Action
{
    public const SESSION_KEY = 'confirmation_flows.v1';

    private const FLOW_TTL_SECONDS = 600; // 10 minutes

    private const MAX_PASSWORD_ATTEMPTS = 3;

    private const REQUEST_STEP_INDEX_KEY = 'confirmation_step_index';

    private Request $request;

    private ?string $title = null;

    private ?string $message = null;

    private string $confirmText;

    private string $cancelText;

    private bool $requiresPassword = false;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->confirmText = (string) __('support::confirmation.confirm');

        $this->cancelText = (string) __('support::confirmation.cancel');
    }

    public function setTitle(?string $title): self
    {
        if ($title === null) {
            return $this;
        }

        $this->title = $title;

        return $this;
    }

    public function setMessage(?string $message): self
    {
        if ($message === null) {
            return $this;
        }

        $this->message = $message;

        return $this;
    }

    public function setConfirmText(?string $confirmText): self
    {
        if ($confirmText === null) {
            return $this;
        }

        $this->confirmText = $confirmText;

        return $this;
    }

    public function setCancelText(?string $cancelText): self
    {
        if ($cancelText === null) {
            return $this;
        }

        $this->cancelText = $cancelText;

        return $this;
    }

    public function setRequiresPassword(bool $requiresPassword): self
    {
        $this->requiresPassword = $requiresPassword;

        return $this;
    }

    public function handle(): bool|string
    {
        $title = $this->title ?? (string) __($this->requiresPassword ? 'support::confirmation.password_title' : 'support::confirmation.title');
        $message = $this->message ?? (string) __($this->requiresPassword ? 'support::confirmation.password_message' : 'support::confirmation.message');
        [$flow, $token] = $this->resolveFlowAndToken($title, $message);

        $exception = (new ConfirmationHttpException)
            ->setFlow($flow)
            ->setToken($token)
            ->setTitle($title)
            ->setMessage($message)
            ->setConfirmText($this->confirmText)
            ->setCancelText($this->cancelText)
            ->setRequiresPassword($this->requiresPassword);

        if (! $this->isConfirmed($flow, $token)) {
            throw $exception;
        }

        if (! $this->requiresPassword) {
            return true;
        }

        $key = $this->passwordAttemptsKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PASSWORD_ATTEMPTS)) {
            throw new RateLimitException;
        }

        $attemptsLeft = self::MAX_PASSWORD_ATTEMPTS - RateLimiter::attempts($key);
        RateLimiter::increment($key);

        $password = (string) $this->request->header('X-Confirmation-Password', '');
        $passwordMatch = Hash::check($password, (string) Auth::user()?->getAuthPassword());

        if (! $passwordMatch) {
            $exception->setErrorMessage(trans_choice('support::confirmation.password_throttle', $attemptsLeft, ['n' => $attemptsLeft]));
            throw $exception;
        }

        RateLimiter::clear($key);

        return $password;
    }

    /**
     * The wrong passwords are counted per user and per action: one user's
     * mistakes never lock another user out of the same action.
     */
    private function passwordAttemptsKey(): string
    {
        return 'confirmation-password:' . Auth::id() . ':' . $this->request->url();
    }

    public static function clearFlow(Request $request, ?string $flow): void
    {
        if (! $request->hasSession() || $flow === null || $flow === '') {
            return;
        }

        $flows = $request->session()->get(self::SESSION_KEY, []);

        if (! is_array($flows) || ! array_key_exists($flow, $flows)) {
            return;
        }

        unset($flows[$flow]);

        $request->session()->put(self::SESSION_KEY, $flows);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveFlowAndToken(string $title, string $message): array
    {
        $flow = $this->flowFingerprint();
        $stepIndex = $this->nextStepIndex();
        $stepFingerprint = $this->stepFingerprint($stepIndex, $title, $message);
        $expiresAt = time() + self::FLOW_TTL_SECONDS;
        $flows = $this->request->session()->get(self::SESSION_KEY, []);

        if (! is_array($flows)) {
            $flows = [];
        }

        $flows = $this->pruneExpiredFlows($flows);

        $flowState = $flows[$flow] ?? [
            'expires_at' => $expiresAt,
            'steps' => [],
        ];

        if (! is_array($flowState)) {
            $flowState = [
                'expires_at' => $expiresAt,
                'steps' => [],
            ];
        }

        $steps = $flowState['steps'] ?? [];

        if (! is_array($steps)) {
            $steps = [];
        }

        $stepState = $steps[$stepIndex] ?? null;

        if (! is_array($stepState) || ($stepState['fingerprint'] ?? null) !== $stepFingerprint || ! is_string($stepState['token'] ?? null) || $stepState['token'] === '') {
            $stepState = [
                'fingerprint' => $stepFingerprint,
                'token' => Str::random(64),
            ];
        }

        $steps[$stepIndex] = $stepState;
        $flowState['steps'] = $steps;
        $flowState['expires_at'] = $expiresAt;
        $flows[$flow] = $flowState;

        $this->request->session()->put(self::SESSION_KEY, $flows);

        return [$flow, $stepState['token']];
    }

    /**
     * @param  array<string, mixed>  $flows
     * @return array<string, mixed>
     */
    private function pruneExpiredFlows(array $flows): array
    {
        $now = time();

        return array_filter($flows, function (mixed $flowState) use ($now): bool {
            if (! is_array($flowState)) {
                return false;
            }

            $expiresAt = $flowState['expires_at'] ?? null;

            if (! is_int($expiresAt)) {
                return false;
            }

            return $expiresAt >= $now;
        });
    }

    private function nextStepIndex(): int
    {
        $stepIndex = (int) $this->request->attributes->get(self::REQUEST_STEP_INDEX_KEY, 0) + 1;

        $this->request->attributes->set(self::REQUEST_STEP_INDEX_KEY, $stepIndex);

        return $stepIndex;
    }

    private function isConfirmed(string $flow, string $token): bool
    {
        $requestFlow = (string) $this->request->header('X-Confirmation-Flow', '');

        if ($requestFlow === '' || ! hash_equals($flow, $requestFlow)) {
            return false;
        }

        return in_array($token, $this->confirmedTokens(), true);
    }

    /**
     * @return array<int, string>
     */
    private function confirmedTokens(): array
    {
        $tokenHeader = (string) $this->request->header('X-Confirmation-Token', '');

        if ($tokenHeader === '') {
            return [];
        }

        $tokens = array_map('trim', explode(',', $tokenHeader));
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));

        return array_values(array_unique($tokens));
    }

    private function flowFingerprint(): string
    {
        $userId = (string) ($this->request->user()?->getAuthIdentifier() ?? 'guest');
        $signature = implode('|', [
            $userId,
            strtoupper($this->request->method()),
            '/' . ltrim($this->request->path(), '/'),
            $this->payloadHash(),
        ]);

        return hash('sha256', $signature);
    }

    private function payloadHash(): string
    {
        $normalizedPayload = $this->normalizePayload($this->request->request->all());
        $payloadJson = json_encode($normalizedPayload, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payloadJson === false) {
            $payloadJson = '';
        }

        return hash('sha256', $payloadJson);
    }

    private function stepFingerprint(int $stepIndex, string $title, string $message): string
    {
        $signature = implode('|', [
            (string) $stepIndex,
            $title,
            $message,
            $this->confirmText,
            $this->cancelText,
            $this->requiresPassword ? '1' : '0',
        ]);

        return hash('sha256', $signature);
    }

    private function normalizePayload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizePayload($item), $value);
        }

        ksort($value);

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $this->normalizePayload($item);
        }

        return $normalized;
    }
}
