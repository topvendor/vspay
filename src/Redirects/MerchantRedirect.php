<?php

namespace Topvendor\Vspay\Redirects;

/**
 * Builds merchant return URLs with the VSPay redirect query contract.
 *
 * Useful when you omit error_redirect_url on charge creation: the platform
 * defaults the error return to success_redirect_url with vspay_status=failed,
 * but you can build the same URL client-side.
 */
final class MerchantRedirect
{
    /**
     * Append vspay status (and optional extras) to a merchant redirect URL,
     * preserving any query string the merchant already put on it.
     *
     * @param  array<string, string|null>  $extra
     */
    public static function withStatus(string $url, string $status, array $extra = []): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $params = [ReturnQuery::STATUS_PARAM => $status];
        foreach ($extra as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[$key] = (string) $value;
            }
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
