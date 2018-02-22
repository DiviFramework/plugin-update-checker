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

			if ($token && !empty($token)) {
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

		// diviframework-client plugin check.
		if (is_admin()) {
			add_action('plugins_loaded', array($this, 'checkPluginDependancy'));
		}

	}

	public function checkPluginDependancy() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$container = $this->container;
		$plugin_path = isset($this->container['hub_plugin_path']) ? $this->container['hub_plugin_path'] : 'diviframework/diviframework.php';

		if (!is_plugin_active($plugin_path)) {
			add_action('admin_notices', function () use ($container) {
				$hub_plugin_name = isset($container['hub_plugin_name']) ? $container['hub_plugin_name'] : 'Divi Framework';
				$hub_plugin_url = isset($container['hub_plugin_url']) ? $container['hub_plugin_url'] : 'https://www.diviframework.com/diviframework-CriQuirdenucoocojUngEucEucEidig';

				$class = 'notice notice-error is-dismissible';

				$message = sprintf('To recieve future updates for %s please install, activate and authenticate the %s plugin. Download <a href="%s">here</a>', $container['plugin_name'], $hub_plugin_name, $hub_plugin_url);

				printf('<div class="%1$s"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', $class, $message);
			});
		}
	}

}