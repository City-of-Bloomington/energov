<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

class MasterAddress
{
    public const ADDRESS_SERVICE_URL = 'https://bloomington.in.gov/master_address';

	public static function parseAddress(string $address): ?array
	{
        $url = self::ADDRESS_SERVICE_URL.'/addresses/parse?'.http_build_query(['format'=>'json', 'address'=>$address], '', ';');
        return self::doJsonRequest($url);
	}

	/**
	 * Creates the full street number portion from a parse response
	 */
	public static function streetNumber(array $parse): string
	{
        $out = [];
        if (isset($parse['street_number_prefix'])) { $out[] = $parse['street_number_prefix']; }
        if (isset($parse['street_number'       ])) { $out[] = $parse['street_number'       ]; }
        if (isset($parse['street_number_suffix'])) { $out[] = $parse['street_number_suffix']; }
        return implode(' ', $out);
	}

	/**
	 * Creates a full subunit label from a parse response
	 */
	public static function subunit(array $parse): string
	{
        $out = [];
        if (isset($parse['subunitType'      ])) { $out[] = $parse['subunitType'      ]; }
        if (isset($parse['subunitIdentifier'])) { $out[] = $parse['subunitIdentifier']; }
        return implode(' ', $out);
	}

	private static function get(string $url): ?string
	{
		$request = curl_init($url);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
		$res     = curl_exec($request);
		return $res ? $res : null;
	}

	private static function doJsonRequest(string $url): ?array
	{
        $res = self::get($url);
        if ($res) {
            return json_decode($res, true);
        }
        return null;
	}
}
