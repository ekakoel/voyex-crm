<?php

namespace App\Services;

class AuditDiffService
{
    /**
     * @return array<int, array{field:string,label:string,from:mixed,to:mixed}>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];
        $this->walk($changes, $this->normalize($before), $this->normalize($after));

        return array_values($changes);
    }

    /**
     * @return array<int, array{field:string,label:string,from:mixed,to:mixed}>
     */
    public function created(array $after): array
    {
        return $this->diff([], $after);
    }

    /**
     * @param array<int, array{field:string,label:string,from:mixed,to:mixed}> $changes
     */
    private function walk(array &$changes, mixed $before, mixed $after, string $path = ''): void
    {
        if (is_array($before) && is_array($after) && $this->isAssoc($before) && $this->isAssoc($after)) {
            $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
            foreach ($keys as $key) {
                $nextPath = $path === '' ? (string) $key : "{$path}.{$key}";
                $this->walk($changes, $before[$key] ?? null, $after[$key] ?? null, $nextPath);
            }
            return;
        }

        if (is_array($before) && is_array($after) && ! $this->isAssoc($before) && ! $this->isAssoc($after)) {
            $length = max(count($before), count($after));
            for ($i = 0; $i < $length; $i++) {
                $nextPath = $path === '' ? (string) $i : "{$path}.{$i}";
                $this->walk($changes, $before[$i] ?? null, $after[$i] ?? null, $nextPath);
            }
            return;
        }

        $normalizedBefore = $this->normalizeValue($before);
        $normalizedAfter = $this->normalizeValue($after);

        if ($normalizedBefore === $normalizedAfter) {
            return;
        }

        $field = $path !== '' ? $path : 'value';
        $changes[] = [
            'field' => $field,
            'label' => $this->labelFromPath($field),
            'from' => $normalizedBefore,
            'to' => $normalizedAfter,
        ];
    }

    private function normalize(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            $normalized[(string) $key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $this->normalizeValue($value->toArray());
            }
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (is_array($value)) {
            $isAssoc = $this->isAssoc($value);
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$isAssoc ? (string) $key : (int) $key] = $this->normalizeValue($item);
            }
            return $normalized;
        }
        if (is_float($value)) {
            return round($value, 4);
        }

        return $value;
    }

    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function labelFromPath(string $path): string
    {
        $segments = explode('.', $path);
        $label = [];
        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $label[] = '#' . ((int) $segment + 1);
                continue;
            }
            $label[] = ucwords(str_replace('_', ' ', $segment));
        }

        return implode(' > ', $label);
    }
}

