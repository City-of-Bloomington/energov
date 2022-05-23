<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

class ArcGIS
{
    private $token;
    private $portal;
    private $server;

    public const REFERER = 'http://localhost';

    public function __construct(array $config)
    {
        $this->portal = $config['portal'].'/sharing/rest';
        $this->server = $config['server'].'/rest/services';

        $this->token = $this->token($config);
        if (!$this->token) {
            throw new \Exception('Could not authenticate to portal');
        }
    }

    /**
     * May return more than one parcel for given coordinates
     *
     * @param string $resource  Path to resource in REST service
     * @param int    $x         State Plane X
     * @param int    $y         State Plane y
     * @return array  An array of parcels
     */
    public function parcels(string $resource, int $x, int $y): ?array
    {
        $url = $this->server.$resource.'/query?'.http_build_query([
            'geometryType'   => 'esriGeometryPoint',
            'geometry'       => "$x,$y",
            'spatialRel'     => 'esriSpatialRelWithin',
            'outFields'      => 'OBJECTID,pin_18,tax_10,owner,legal_desc',
            'f'              => 'json',
            'returnGeometry' => 'false',
            'token'          => $this->token
        ], '', '&');
        $res = $this->get($url);
        if ($res) {
            $json = json_decode($res, true);
            if (!empty($json['features'])) {
                $out = [];
                foreach ($json['features'] as $parcel) {
                    $out[] = $parcel['attributes'];
                }
                return $out;
            }
        }
        return null;
    }

    private function token(array $config): ?string
    {
        $res = self::post($this->portal.'/generateToken', [
            'username'  => $config['user'  ],
            'password'  => $config['pass'  ],
            'client'    => 'referer',
            'referer'   => self::REFERER,
            'f'         => 'json'
        ]);
        if ($res) {
            $json = json_decode($res, true);
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
        curl_setopt($request, CURLOPT_HTTPHEADER, ['Referer: '.self::REFERER]);
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
