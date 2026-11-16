<?php

namespace App\Logging;

use Monolog\LogRecord;
use Throwable;

class RedactSensitiveData
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        return $record->with(
            message: $exception instanceof Throwable ? 'Application exception' : $this->redact($record->message),
            context: $this->sanitize($record->context),
            extra: $this->sanitize($record->extra),
        );
    }

    /**
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|authorization|cookie|signature|body|payload|email|mail|url|uri|query/i', $key)) {
                $values[$key] = '[redacted]';
            } elseif ($value instanceof Throwable) {
                $values[$key] = [
                    'class' => $value::class,
                    'file' => $value->getFile(),
                    'line' => $value->getLine(),
                ];
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $values[$key] = $this->redact($value);
            } elseif (is_object($value)) {
                $values[$key] = '[object '.$value::class.']';
            }
        }

        return $values;
    }

    private function redact(string $value): string
    {
        $value = preg_replace('~(?:https?://[^\s]+|/(?:reset-password|email/verify)/[^\s]+)~i', '[redacted-url]', $value) ?? '[redacted]';

        return preg_replace('/\b(password|token|secret|authorization|cookie|signature)\s*[:=]\s*[^\s,;]+/i', '$1=[redacted]', $value) ?? '[redacted]';
    }
}
