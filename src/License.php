<?php

namespace DiviFramework\UpdateChecker;

class License {
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

		// add_filter($this->updateChecker->getUniqueName('request_info_options'), array($this, 'request_info_options'));

		// add authorization header.
		$key = $this->tokenOptionsKey;
		$this->updateChecker->addHttpRequestArgFilter(function ($options) use ($key) {
			$additionalHeaders = array();
			$token = get_option($key, false);

			if (!$token) {
				$additionalHeaders['Authorization'] = 'Bearer ' . $token;
			}

			$options['headers'] = array_merge($options['headers'], $additionalHeaders);
			return $options;
		});

	}

}