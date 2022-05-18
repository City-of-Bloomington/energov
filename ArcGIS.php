<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

class ArcGIS
{
    private $portal;
    private $server;
    private $portal_token;
    private $server_token;

    public function __construct(array $config)
    {
        $this->portal = $config['portal'].'/sharing/rest';
        $this->server = $config['server'].'/rest/service';

        $this->portal_token = $this->portalToken($config);
        if (!$this->portal_token) {
            throw new \Exception('Could not authenticate to portal');
        }
        $this->server_token = $this->serverToken($config);
        if (!$this->server_token) {
            throw new \Exception('Could not authenticate to server');
        }
    }

    /**
     * @param string $resource  Path to resource in REST service
     * @param int    $x         State Plane X
     * @param int    $y         State Plane y
     */
    public function parcelInfo(string $resource, int $x, int $y): ?array
    {
        $url = $this->server.$resource.'/query?'.http_build_query([
            'geometryType'   => 'esriGeometryEnvelope',
            'geometry'       => "$x,$y",
            'outFields'      => 'OBJECTID,pin_18,tax_10,owner,legal_desc',
            'f'              => 'json',
            'returnGeometry' => 'false'
        ], '', '&');
        echo "$url\n";
        $res = $this->get($url);
        print_r($res);
        return null;
    }

    private function portalToken(array $config): ?string
    {
        $res = self::post($this->portal.'/generateToken', [
            'username'  => $config['user'  ],
            'password'  => $config['pass'  ],
            'client'    => 'requestip',
            'f'         => 'json'
        ]);
        print_r($res);
        if ($res) {
            $json = json_decode($res, true);
            print_r($json);
            if (!empty($json['token'])) {
                return $json['token'];
            }
        }
        return null;
    }

    private function serverToken(array $config): ?string
    {
        $res = self::post($this->portal.'/generateToken', [
            'serverURL' => $config['server'],
            'username'  => $config['user'  ],
            'password'  => $config['pass'  ],
            'client'    => 'requestip',
            'f'         => 'json',
            'token'     => $this->portal_token
        ]);
        if ($res) {
            $json = json_decode($res, true);
            print_r($json);
            if (!empty($json['token'])) {
                return $json['token'];
            }
        }
        return null;
    }

	private function get(string $url): ?string
	{
		$request = curl_init($url);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($request, CURLOPT_HTTPHEADER, [
            "X-Esri-Authorization: Bearer {$this->portal_token}"
        ]);
		$res     = curl_exec($request);
		return $res ? $res : null;
	}

	private static function post(string $url, array $params)
    {
		$request = curl_init($url);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($request, CURLOPT_POST,           true);
        curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
		$res     = curl_exec($request);
		return $res ? $res : null;
    }
}
