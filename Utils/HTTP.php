<?php
namespace SAMLBundle\Utils;

class HTTP
{
    public static function redirectTrustedURL(string $url, array $parameters = [])
    {
        $normalizedURL = self::normalizeURL($url);
        self::redirect($normalizedURL, $parameters);
    }
    
    public static function normalizeURL(string $url)
    {
        $resolvedURL = self::resolveURL($url, self::getSelfURL());

        // verify that the URL is to a http or https site
        if (!preg_match('@^https?://@i', $resolvedURL )) {
            throw new \InvalidArgumentException('Invalid URL: '.$resolvedURL);
        }

        return $resolvedURL;
    }
    
    public static function getSelfURL()
    {
        $baseDir = '/'; //baseurlpath $cfg->getBaseDir();
        $cur_path = realpath($_SERVER['SCRIPT_FILENAME']);
        // make sure we got a string from realpath()
        $cur_path = is_string($cur_path) ? $cur_path : '';
        // find the path to the current script relative to the www/ directory of SimpleSAMLphp
        $rel_path = str_replace($baseDir.'www'.DIRECTORY_SEPARATOR, '', $cur_path);
        // convert that relative path to an HTTP query
        $url_path = str_replace(DIRECTORY_SEPARATOR, '/', $rel_path);
        
        // find where the relative path starts in the current request URI
        $uri_pos = (!empty($url_path)) ? strpos($_SERVER['REQUEST_URI'], $url_path) : false;

        if ($cur_path == $rel_path || $uri_pos === false) {
            $protocol = 'http';
            $protocol .= (self::getServerHTTPS()) ? 's' : '';
            $hostname = self::getServerHost();
            $port = self::getServerPort();
            return $protocol.'://'.$hostname.$port.$_SERVER['REQUEST_URI'];
        }
        
        return self::getBaseURL().$url_path.substr($_SERVER['REQUEST_URI'], $uri_pos + strlen($url_path));
    }
    
    public static function getServerHTTPS()
    {
        if (!array_key_exists('HTTPS', $_SERVER)) {
            // not an https-request
            return false;
        }

        if ($_SERVER['HTTPS'] === 'off') {
            // IIS with HTTPS off
            return false;
        }

        // otherwise, HTTPS will be non-empty
        return !empty($_SERVER['HTTPS']);
    }
    
    private static function getServerHost()
    {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $current = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $current = $_SERVER['SERVER_NAME'];
        } else {
            // almost certainly not what you want, but...
            $current = 'localhost';
        }

        if (strstr($current, ":")) {
            $decomposed = explode(":", $current);
            $port = array_pop($decomposed);
            if (!is_numeric($port)) {
                array_push($decomposed, $port);
            }
            $current = implode(":", $decomposed);
        }
        return $current;
    }
    
    public static function getServerPort()
    {
        $default_port = self::getServerHTTPS() ? '443' : '80';
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : $default_port;

        // Take care of edge-case where SERVER_PORT is an integer
        $port = strval($port);
        
        if ($port !== $default_port) {
            return ':'.$port;
        }
        return '';
    }
    
    public static function resolveURL(string $url, string $base = null)
    {
        if ($base === null) {
            $base = self::getBaseURL();
        }

        if (!preg_match('/^((((\w+:)\/\/[^\/]+)(\/[^?#]*))(?:\?[^#]*)?)(?:#.*)?/', $base, $baseParsed)) {
            throw new \InvalidArgumentException('Unable to parse base url: '.$base);
        }

        $baseDir = dirname($baseParsed[5].'filename');
        $baseScheme = $baseParsed[4];
        $baseHost = $baseParsed[3];
        $basePath = $baseParsed[2];
        $baseQuery = $baseParsed[1];

        if (preg_match('$^\w+:$', $url)) {
            return $url;
        }

        if (substr($url, 0, 2) === '//') {
            return $baseScheme.$url;
        }

        if ($url[0] === '/') {
            return $baseHost.$url;
        }
        if ($url[0] === '?') {
            return $basePath.$url;
        }
        if ($url[0] === '#') {
            return $baseQuery.$url;
        }

        // we have a relative path. Remove query string/fragment and save it as $tail
        $queryPos = strpos($url, '?');
        $fragmentPos = strpos($url, '#');
        if ($queryPos !== false || $fragmentPos !== false) {
            if ($queryPos === false) {
                $tailPos = $fragmentPos;
            } elseif ($fragmentPos === false) {
                $tailPos = $queryPos;
            } elseif ($queryPos < $fragmentPos) {
                $tailPos = $queryPos;
            } else {
                $tailPos = $fragmentPos;
            }

            $tail = substr($url, $tailPos);
            $dir = substr($url, 0, $tailPos);
        } else {
            $dir = $url;
            $tail = '';
        }

        $dir = System::resolvePath($dir, $baseDir);

        return $baseHost.$dir.$tail;
    }
    
    private static function redirect(string $url, array $parameters = [])
    {
        if (!empty($parameters)) {
            $url = self::addURLParameters($url, $parameters);
        }

        /* Set the HTTP result code. This is either 303 See Other or
         * 302 Found. HTTP 303 See Other is sent if the HTTP version
         * is HTTP/1.1 and the request type was a POST request.
         */
        if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $code = 303;
        } else {
            $code = 302;
        }

        if (strlen($url) > 2048) {
            Logger::warning('Redirecting to a URL longer than 2048 bytes.');
        }

        if (!headers_sent()) {
            // set the location header
            header('Location: '.$url, true, $code);

            // disable caching of this response
            header('Pragma: no-cache');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        // show a minimal web page with a clickable link to the URL
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"';
        echo ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
        echo "  <head>\n";
        echo '    <meta http-equiv="content-type" content="text/html; charset=utf-8">'."\n";
        echo '    <meta http-equiv="refresh" content="0;URL=\''.htmlspecialchars($url).'\'">'."\n";
        echo "    <title>Redirect</title>\n";
        echo "  </head>\n";
        echo "  <body>\n";
        echo "    <h1>Redirect</h1>\n";
        echo '      <p>You were redirected to: <a id="redirlink" href="'.htmlspecialchars($url).'">';
        echo htmlspecialchars($url)."</a>\n";
        echo '        <script type="text/javascript">document.getElementById("redirlink").focus();</script>'."\n";
        echo "      </p>\n";
        echo "  </body>\n";
        echo '</html>';

        // end script execution
        exit;
    }
    
    public static function addURLParameters(string $url, array $parameters)
    {
        $queryStart = strpos($url, '?');
        if ($queryStart === false) {
            $oldQuery = array();
            $url .= '?';
        } else {
            /** @var string|false $oldQuery */
            $oldQuery = substr($url, $queryStart + 1);
            if ($oldQuery === false) {
                $oldQuery = array();
            } else {
                $oldQuery = self::parseQueryString($oldQuery);
            }
            $url = substr($url, 0, $queryStart + 1);
        }

        /** @var array $oldQuery */
        $query = array_merge($oldQuery, $parameters);
        $url .= http_build_query($query, '', '&');

        return $url;
    }
}