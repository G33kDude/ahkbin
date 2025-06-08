<?php

declare(strict_types=1);

namespace AhkBin;

use Error;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Http\Response as Response;
use Throwable;

class Routes
{
    static $pastes = '/pastes/';
    static $cookie_time = 60 * 60 * 24 * 30; // 30 days

    /**
     * GET /
     * 
     * Render the index page
     */
    static function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(Templates::index(
            false,
            getenv('BASE_URL'),
            '',
            $request->getCookieParams()['name'] ?? '',
            ''
        ));
        return $response->withAddedHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * GET /?r={pasteid}
     * 
     * Render a paste as text/plain
     */
    static function viewPasteRaw(Request $request, Response $response): Response
    {
        $filepath = self::$pastes . AhkUtils::strip($request->getQueryParams()['r']);

        $contents = file_get_contents($filepath);
        if ($contents === false)
            return $response->withAddedHeader('Location', './');

        $response->getBody()->write($contents);
        return $response->withAddedHeader('Content-type', 'text/plain; charset=utf-8');
    }

    /**
     * GET /?e={pasteid}
     * 
     * Render a the index page with a paste that can be edited
     */
    static function viewPasteEdit(Request $request, Response $response): Response
    {
        $hash = AhkUtils::strip($request->getQueryParams()['e']);

        $response->getBody()->write(Templates::index(
            false,
            getenv('BASE_URL'),
            $hash,
            $request->getCookieParams()['name'] ?? '',
            file_get_contents(self::$pastes . $hash) ?: '',
        ));
        return $response->withAddedHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * GET /?p={pasteid}
     * 
     * Render a the index page with a paste that can not be edited
     */
    static function viewPaste(Request $request, Response $response): Response
    {
        $url = getenv('BASE_URL');

        $hash = AhkUtils::strip($request->getQueryParams()['p']);
        $response = $response->withAddedHeader('ahk-location', "$url/?p=$hash");

        $response->getBody()->write(Templates::index(
            true,
            $url,
            $hash,
            $request->getCookieParams()['name'] ?? '',
            file_get_contents(self::$pastes . $hash) ?: '',
        ));
        return $response->withAddedHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * POST /
     * 
     * Create a new paste
     * 
     * param code string  The code for the paste
     * param name string? The name to use when announcing the paste
     * param desc string? The description to use when announcing the paste
     */
    static function makePaste(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        if ($body === null)
            throw new HttpBadRequestException($request);

        $code = $body['code'] ?? '';
        if (trim($code) === '')
            return $response->withAddedHeader('Location', './');

        // Save the code with the filename as a partial sha1 hash
        $hash = substr(sha1($code), 0, 8);
        file_put_contents(self::$pastes . $hash, $code);

        // Save the submitted nickname as a cookie
        $name = substr(trim($body['name'] ?? ''), 0, 16);
        $response = $response->withAddedHeader(
            'Set-Cookie',
            join('; ', [
                'name=' . urlencode($name),
                'Path=/',
                'Max-Age=' . self::$cookie_time,
                'HttpOnly',
                'SameSite=Strict'
            ])
        );

        // Announce to IRC
        $channel = $body['channel'] ?? false;
        if ($channel && getenv('IRC_ANNOUNCE_ENABLE') === 'true') {
            $desc = substr(trim($body['desc'] ?? ''), 0, 128);

            // Choose which channel to announce in

            // Create the API call
            $url = getenv('BASE_URL');
            $in = json_encode([
                'Action' => '__Call',
                'Name' => 'Chat',
                'Params' => [
                    $channel,
                    '[ahkbin] ' . ($name ?: 'Anonymous')
                        . " just pasted $url/?p=$hash"
                        . ($desc ? " - $desc" : "")
                ]
            ]) . "\r\n";

            // Call the API
            try {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if ($socket === false)
                    throw new Error();
                $result = socket_connect($socket, getenv('IRC_ANNOUNCE_HOST'), (int)getenv('IRC_ANNOUNCE_PORT'));
                if ($result === false)
                    throw new Error();
                socket_write($socket, $in, strlen($in));
                socket_close($socket);
            } catch (Throwable $e) {
            }
        }

        return $response->withAddedHeader('Location', "./?p=$hash");
    }

    /**
     * DELETE /?p={paste_ids}
     * 
     * Deletes one or more pastes by comma separated ID
     */
    static function deletePaste(Request $request, Response $response): Response
    {
        // Validate Credentials
        $delete_credentials = getenv('DELETE_CREDENTIALS');
        if (!$delete_credentials)
            throw new HttpInternalServerErrorException($request, "I'm not configured correctly");
        $auth_header = $request->getHeader('Authorization')[0] ?? null;
        if (!str_starts_with($auth_header, 'Basic '))
            throw new HttpUnauthorizedException($request, 'Authorization missing');
        if ($delete_credentials !== base64_decode(substr($auth_header, 6)))
            throw new HttpUnauthorizedException($request, 'Bad credentials');

        // Retrieve paste IDs from query params
        $paste_ids = $request->getQueryParams()['p'] ?? '';
        if (!$paste_ids)
            throw new HttpBadRequestException($request, 'No paste IDs provided');

        // Delete pastes
        $succeeded = 0;
        $failed = 0;
        foreach (explode(',', $paste_ids) as $id) {
            $file = self::$pastes . AhkUtils::strip($id);
            if (!file_exists($file))
                continue;

            if (unlink($file))
                $succeeded++;
            else
                $failed++;
        }

        return $response->withJson([
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);
    }
}
