<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    protected function handleApiException(Throwable $e)
    {
        return match (true) {
            $e instanceof ValidationException => response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $e->errors(),
            ], 422),

            $e instanceof AuthenticationException => response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi. Silakan login kembali.',
                'errors' => [],
            ], 401),

            $e instanceof AuthorizationException => response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
                'errors' => [],
            ], 403),

            $e instanceof ModelNotFoundException => response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
                'errors' => [],
            ], 404),

            $e instanceof NotFoundHttpException => response()->json([
                'success' => false,
                'message' => 'Endpoint tidak ditemukan.',
                'errors' => [],
            ], 404),

            $e instanceof ThrottleRequestsException => response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                'errors' => [],
            ], 429),

            $e instanceof VaultAccessDeniedException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'reason' => $e->reason,
                    'vault_id' => $e->vaultId,
                    'user_id' => $e->userId,
                ],
            ], 403),

            $e instanceof DeviceAuthenticationException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'serial_number' => $e->serialNumber,
                    'reason' => $e->reason,
                ],
            ], 401),

            $e instanceof SessionTimeoutException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'session_id' => $e->sessionId,
                    'vault_id' => $e->vaultId,
                    'duration' => $e->duration,
                ],
            ], 408),

            $e instanceof MqttConnectionException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'broker' => $e->broker,
                    'reason' => $e->reason,
                ],
            ], 503),

            $e instanceof WorkingTimeViolationException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'branch_id' => $e->branchId,
                    'vault_id' => $e->vaultId,
                    'attempted_at' => $e->attemptedAt,
                ],
            ], 403),

            default => response()->json([
                'success' => false,
                'message' => app()->isProduction()
                    ? 'Terjadi kesalahan internal server.'
                    : $e->getMessage(),
                'errors' => app()->isProduction() ? [] : [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500),
        };
    }
}
