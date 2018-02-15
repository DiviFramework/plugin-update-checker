<?php

namespace DiviFramework\UpdateChecker;

use Puc_v4_Factory;

class PluginLicense {
	protected $container;
	protected $baseUrl;
	protected $tokenOptionsKey;
	public $updateChecker;

	public function __construct($container, $baseUrl, $tokenOptionsKey = 'df-token') {
		$this->container = $container;
		$this->baseUrl = $baseUrl;
		$this->tokenOptionsKey = $tokenOptionsKey;
	}

	public function init() {
		$this->updateChecker = Puc_v4_Factory::buildUpdateChecker(
			$this->baseUrl . '/wp-json/wordpress-extensions/v1/updates/' . $this->container['plugin_slug'],
			$this->container['plugin_file'],
			$this->container['plugin_slug']
		);

		// add authorization header.
		$key = $this->tokenOptionsKey;
		$this->updateChecker->addHttpRequestArgFilter(function ($options) use ($key) {
			$additionalHeaders = array();
			$token = get_option($key, false);

			if (!$token && !empty($token)) {
				$additionalHeaders['Authorization'] = 'Bearer ' . $token;
			} else {
				// ensure option is delete. Next page reload will prompt for login.
				delete_option($key);
			}

			$options['headers'] = array_merge($options['headers'], $additionalHeaders);
			return $options;
		});

		add_filter($this->updateChecker->getUniqueName('request_metadata_http_result'), function ($result) use ($key) {
			//token error.
			if ($result['response']['code'] == 403) {
				// remove option
				delete_option($key);
				// redirect to login page?
			}
			return $result;
		});
	}

}