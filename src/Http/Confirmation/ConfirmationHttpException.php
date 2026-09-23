<?php

namespace Witify\Support\Http\Confirmation;

use Exception;
use Illuminate\Http\JsonResponse;
use Witify\Support\Exception\ShouldntReportOnRequest;

class ConfirmationHttpException extends Exception implements ShouldntReportOnRequest
{
    private string $title;

    private ?string $errorMessage = null;

    private string $confirmText;

    private string $cancelText;

    private bool $requiresPassword = false;

    private ?string $flow = null;

    private ?string $token = null;

    public function setFlow(?string $flow): self
    {
        if ($flow === null) {
            return $this;
        }

        $this->flow = $flow;

        return $this;
    }

    public function setToken(?string $token): self
    {
        if ($token === null) {
            return $this;
        }

        $this->token = $token;

        return $this;
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

    public function setErrorMessage(?string $errorMessage): self
    {
        if ($errorMessage === null) {
            return $this;
        }

        $this->errorMessage = $errorMessage;

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

    public function render(): JsonResponse
    {
        return response()->json([
            'code' => 'confirmation_required',
            'message' => __('support::confirmation.required'),
            'confirmation' => [
                'flow' => $this->flow,
                'token' => $this->token,
                'title' => $this->title,
                'message' => $this->message,
                'errorMessage' => $this->errorMessage,
                'confirmText' => $this->confirmText,
                'cancelText' => $this->cancelText,
                'requiresPassword' => $this->requiresPassword,
            ],
        ], 428);
    }
}
