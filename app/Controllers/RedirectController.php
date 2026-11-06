<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Click;
use App\Models\Shortlink;

class RedirectController extends Controller
{
    public function handle(string $code): void
    {
        $code = trim($code);
        if ($code === '') {
            self::renderError(404, 'Shortlink Not Found', 'The shortlink you visited could not be found.');
        }

        // 1. Lookup shortlink (case-sensitive binary match)
        $shortlinkModel = new Shortlink();
        $shortlink = $shortlinkModel->findByCode($code);

        // 2. Ensure shortlink exists
        if (!$shortlink) {
            self::renderError(404, 'Shortlink Not Found', 'This shortlink does not exist or may have been deleted.', [
                'shortCode' => $code,
            ]);
        }

        // 3. Ensure status is active
        if (($shortlink['status'] ?? '') !== 'active') {
            self::renderError(410, 'Link Inactive', 'This shortlink has been deactivated by its owner.', [
                'error_view' => 'errors/inactive',
                'shortCode'  => $code,
                'shortlink'  => $shortlink,
            ]);
        }

        // 4. Check expiration date
        if (!empty($shortlink['expires_at']) && strtotime((string) $shortlink['expires_at']) <= time()) {
            self::renderError(410, 'Link Expired', 'This shortlink has passed its expiration date and is no longer active.', [
                'error_view' => 'errors/expired',
                'reason'     => 'expired',
                'shortCode'  => $code,
                'shortlink'  => $shortlink,
            ]);
        }

        // 5. Check maximum clicks limit
        if (!empty($shortlink['max_clicks']) && (int) $shortlink['total_clicks'] >= (int) $shortlink['max_clicks']) {
            self::renderError(410, 'Click Limit Reached', 'This shortlink has reached its maximum allowed clicks.', [
                'error_view' => 'errors/expired',
                'reason'     => 'max_clicks',
                'shortCode'  => $code,
                'shortlink'  => $shortlink,
            ]);
        }

        // Validate destination URL before redirecting to prevent unsafe redirect schemes
        $destinationUrl = (string) $shortlink['destination_url'];
        $urlCheck = Security::validateDestinationUrl($destinationUrl);
        if (!$urlCheck['valid']) {
            self::renderError(404, 'Invalid Destination', 'The destination URL for this shortlink is invalid.');
        }

        // 6. Parse visitor analytics metadata
        $ip = Security::clientIp();
        $uaString = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $uaInfo = parse_user_agent($uaString);
        $geo = resolve_geo_location($ip);

        $referrer = isset($_SERVER['HTTP_REFERER']) ? trim((string) $_SERVER['HTTP_REFERER']) : null;
        $referrerDomain = 'Direct';
        if (!empty($referrer)) {
            $host = parse_url($referrer, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $referrerDomain = preg_replace('/^www\./i', '', strtolower($host));
            }
        }

        // Extract UTM parameters from incoming request query OR destination URL query string
        $destQuery = [];
        $destQueryStr = parse_url($destinationUrl, PHP_URL_QUERY);
        if (is_string($destQueryStr) && $destQueryStr !== '') {
            parse_str($destQueryStr, $destQuery);
        }

        $utmSource   = trim((string) ($_GET['utm_source'] ?? $destQuery['utm_source'] ?? ''));
        $utmMedium   = trim((string) ($_GET['utm_medium'] ?? $destQuery['utm_medium'] ?? ''));
        $utmCampaign = trim((string) ($_GET['utm_campaign'] ?? $destQuery['utm_campaign'] ?? ''));
        $utmTerm     = trim((string) ($_GET['utm_term'] ?? $destQuery['utm_term'] ?? ''));
        $utmContent  = trim((string) ($_GET['utm_content'] ?? $destQuery['utm_content'] ?? ''));

        $clickData = [
            'ip_address'      => $ip,
            'user_agent'      => $uaString,
            'browser'         => $uaInfo['browser'],
            'os'              => $uaInfo['os'],
            'device_type'     => $uaInfo['device_type'],
            'country'         => $geo['country'],
            'city'            => $geo['city'],
            'referrer'        => $referrer,
            'referrer_domain' => $referrerDomain,
            'utm_source'      => $utmSource !== '' ? $utmSource : null,
            'utm_medium'      => $utmMedium !== '' ? $utmMedium : null,
            'utm_campaign'    => $utmCampaign !== '' ? $utmCampaign : null,
            'utm_term'        => $utmTerm !== '' ? $utmTerm : null,
            'utm_content'     => $utmContent !== '' ? $utmContent : null,
            'is_bot'          => $uaInfo['is_bot'],
        ];

        // Atomically record click & check concurrency limit
        $clickModel = new Click();
        $recorded = $clickModel->recordClickAndIncrement((int) $shortlink['id'], $clickData);

        if (!$recorded) {
            self::renderError(410, 'Link Unavailable', 'This shortlink has just reached its click limit or expired.', [
                'error_view' => 'errors/expired',
                'reason'     => 'max_clicks',
                'shortCode'  => $code,
                'shortlink'  => $shortlink,
            ]);
        }

        // 7. Redirect with HTTP 302 (or configured status code)
        $statusCode = (int) app_setting('redirect_status_code', 302);
        if (!in_array($statusCode, [301, 302, 307], true)) {
            $statusCode = 302;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . $destinationUrl, true, $statusCode);
        exit;
    }
}

