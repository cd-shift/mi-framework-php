<?php

declare(strict_types=1);

namespace Validation\Rules;

class Email implements ValidationRule
{
    public function message(): string
    {
        return 'Email has invalid format';
    }

    public function isValid(string $field, array $data): bool
    {
        if (!array_key_exists($field, $data) || !is_string($data[$field])) {
            return false;
        }

        $email = strtolower(trim($data[$field]));

        if ($email === '') {
            return false;
        }

        $split = explode('@', $email);

        if (count($split) !== 2) {
            return false;
        }

        [$username, $domain] = $split;

        $split = explode('.', $domain);

        if (count($split) !== 2) {
            return false;
        }

        [$label, $topLevelDomain] = $split;

        return strlen($username) >= 1
            && strlen($label) >= 1
            && strlen($topLevelDomain) >= 1;
    }
}
