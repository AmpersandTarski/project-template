<?php

namespace SIAM\Utils;

use InvalidArgumentException;

function array_filter_values(array $array, ?callable $callback): array
{
    // Use array_values() to return clean non-associative array starting from index 0
    return is_null($callback) ? array_values(array_filter($array)) : array_values(array_filter($array, $callback));
}

function flatmap(callable $fn, array $array): array
{
    // Due to the fact that splat (...) of empty array will evaluate to nothing and array_merge expects at least 1 param, we add '[]' as first param
    return array_merge([], ...array_map($fn, $array));
}

/**
 * Returns array of non-null values
 */
function array_filter_null(array $array): array
{
    // Use array_values() to return clean non-associative array starting from index 0
    return array_values(array_filter($array, fn ($item) => !is_null($item)));
}

function sortAlphanum(int|string $a, int|string $b): int
{
    if (is_string($a) && is_string($b)) {
        return strcmp($a, $b);
    }

    if (is_int($a) && is_int($b)) {
        return $a - $b;
    }

    if (is_int($a)) {
        return -1;
    }

    return 1;
}

function getHashSecret(): string
{
    $hashKey = getenv('STH_SECRET_HASHKEY', true);

    // If not (correctly) set as env variable, initialize a secret hashkey for this session.
    // NOTE! This solution won't work for deployments that have multiple replicates of this container,
    // because the secret is not shared between containers. It also won't work across sessions. If that
    // is needed set the environment variable.
    if ($hashKey === false || $hashKey === "" || is_array($hashKey)) {
        // Initialize if not set yet
        if (!array_key_exists('sth_secret_hashkey', $_SESSION)) {
            $_SESSION['sth_secret_hashkey'] = hash("sha256", (string) rand()); // generate a secret if not configured
        }

        $hashKey = $_SESSION['sth_secret_hashkey'];
    }

    return $hashKey;
}


function paramToBool($value): bool
{
    // Returns true for "1", "true", "on" and "yes"
    // Return false for "0", "false", "off", "no", and ""
    // Null is returned for all non-boolean values.
    $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    if (is_null($bool)) {
        throw new InvalidArgumentException("Invalid argument. Cannot convert '{$value}' to boolean");
    }

    return $bool;
}
