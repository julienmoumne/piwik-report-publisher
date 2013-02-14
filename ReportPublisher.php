<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id$
 * 
 * @category Piwik_Plugins
 * @package Piwik_ReportPublisher
 */

/**
 *
 * @package Piwik_ReportPublisher
 */
class Piwik_ReportPublisher extends Piwik_Plugin
{
	const FTP_TYPE = 'ftp';
	const FTP_URI_PARAMETER = 'ftpUri';

	static private $managedReportTypes = array(
		self::FTP_TYPE => 'plugins/ReportPublisher/images/ftp.png'
	);

	static private $managedReportFormats = array(
		Piwik_ReportRenderer::PDF_FORMAT => 'plugins/UserSettings/images/plugins/pdf.gif'
	);

	static private $availableParameters = array(
		self::FTP_URI_PARAMETER => true,
	);
	/**
	 * Return information about this plugin.
	 *
	 * @see Piwik_Plugin
	 *
	 * @return array
	 */
	public function getInformation()
	{
		return array(
			'name' => 'Report Publisher Plugin',
			'description' => Piwik_Translate('ReportPublisher_PluginDescription'),
			'homepage' => 'http://piwik.org/',
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'license' => 'GPL v3 or later',
			'license_homepage' => 'http://www.gnu.org/licenses/gpl.html',
			'version' => '0.1',
			'translationAvailable' => true,
		);
	}

	function getListHooksRegistered()
	{
		return array(
			'PDFReports.getReportTypes' => 'getReportTypes',
			'PDFReports.getReportFormats' => 'getReportFormats',
			'PDFReports.validateReportParameters' => 'validateReportParameters',
			'PDFReports.getReportParameters' => 'getReportParameters',
			'PDFReports.getReportMetadata' => 'getReportMetadata',
			'template_reportParametersPDFReports' => 'template_reportParametersPDFReports',
			'PDFReports.getReportRecipients' => 'getReportRecipients',
			'PDFReports.allowMultipleReports' => 'allowMultipleReports',
			'PDFReports.getRendererInstance' => 'getRendererInstance',
			'PDFReports.processReports' => 'processReports',
			'PDFReports.sendReport' => 'sendReport',
		);
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function getReportTypes( $notification )
	{
		$reportTypes = &$notification->getNotificationObject();
		$reportTypes = array_merge($reportTypes, self::$managedReportTypes);
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function getReportFormats( $notification )
	{
		if(self::manageEvent($notification))
		{
			$reportFormats = &$notification->getNotificationObject();
			$reportFormats = self::$managedReportFormats;
		}
	}

	private static function manageEvent($notification)
	{
		$notificationInfo = $notification->getNotificationInfo();
		return in_array(
			$notificationInfo[Piwik_PDFReports_API::REPORT_TYPE_INFO_KEY],
			array_keys(self::$managedReportTypes)
		);
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function validateReportParameters( $notification )
	{
		if(self::manageEvent($notification))
		{
			$parameters = &$notification->getNotificationObject();

			// TODO validate FTP URI
		}
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function getReportParameters( $notification )
	{
		if(self::manageEvent($notification))
		{
			$availableParameters = &$notification->getNotificationObject();
			$availableParameters = self::$availableParameters;
		}
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function getReportMetadata( $notification )
	{
		if(self::manageEvent($notification))
		{
			$reportMetadata = &$notification->getNotificationObject();

			$notificationInfo = $notification->getNotificationInfo();
			$idSite = $notificationInfo[Piwik_PDFReports_API::ID_SITE_INFO_KEY];

			$availableReportMetadata = Piwik_API_API::getInstance()->getReportMetadata($idSite);

			$filteredReportMetadata = array();
			foreach($availableReportMetadata as $reportMetadata)
			{
				// removing reports from the API category and MultiSites.getOne
				if(
					$reportMetadata['category'] == 'API' ||
					$reportMetadata['category'] == Piwik_Translate('General_MultiSitesSummary') && $reportMetadata['name'] == Piwik_Translate('General_SingleWebsitesDashboard')
				) continue;

				$filteredReportMetadata[] = $reportMetadata;
			}

			$reportMetadata = $filteredReportMetadata;
		}
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	static public function template_reportParametersPDFReports($notification)
	{
		$out =& $notification->getNotificationObject();

		$view = Piwik_View::factory('ReportParameters');
		$view->reportType = self::FTP_TYPE;
		$out .= $view->render();
	}

	function getReportRecipients( $notification )
	{
		if(self::manageEvent($notification))
		{
			$recipients = &$notification->getNotificationObject();
			$notificationInfo = $notification->getNotificationInfo();

			$report = $notificationInfo[Piwik_PDFReports_API::REPORT_KEY];
			$recipients = $report['parameters'][self::FTP_URI_PARAMETER];
		}
	}


	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function allowMultipleReports( $notification )
	{
		if(self::manageEvent($notification))
		{
			$allowMultipleReports = &$notification->getNotificationObject();
			$allowMultipleReports = true;
		}
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function getRendererInstance( $notification )
	{
		if(self::manageEvent($notification))
		{
			$reportRenderer = &$notification->getNotificationObject();
			$notificationInfo = $notification->getNotificationInfo();

			$reportFormat = $notificationInfo[Piwik_PDFReports_API::REPORT_KEY]['format'];

			$reportRenderer = Piwik_ReportRenderer::factory($reportFormat);
		}
	}

	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function processReports( $notification )
	{
		if(self::manageEvent($notification))
		{
			$processedReports = &$notification->getNotificationObject();

			$notificationInfo = $notification->getNotificationInfo();
			$report = $notificationInfo[Piwik_PDFReports_API::REPORT_KEY];

			foreach ($processedReports as &$processedReport)
			{
				$metadata = $processedReport['metadata'];

				$processedReport['displayTable'] = true;

				$processedReport['displayGraph'] =
						Piwik::isGdExtensionEnabled()
						&& Piwik_PluginsManager::getInstance()->isPluginActivated('ImageGraph')
						&& !empty($metadata['imageGraphUrl']);

				// remove evolution metrics from MultiSites.getAll
				if($metadata['module'] == 'MultiSites')
				{
					$columns = $processedReport['columns'];

					foreach(Piwik_MultiSites_API::getApiMetrics($enhanced = true) as $metricSettings)
					{
						unset($columns[$metricSettings[Piwik_MultiSites_API::METRIC_EVOLUTION_COL_NAME_KEY]]);
					}

					$processedReport['metadata'] = $metadata;
					$processedReport['columns'] = $columns;
				}
			}
		}
	}


	/**
	 * @param Piwik_Event_Notification $notification notification object
	 */
	function sendReport( $notification )
	{
		if(self::manageEvent($notification))
		{
			$notificationInfo = $notification->getNotificationInfo();
			$report = $notificationInfo[Piwik_PDFReports_API::REPORT_KEY];
			$websiteName = $notificationInfo[Piwik_PDFReports_API::WEBSITE_NAME_KEY];
			$prettyDate = $notificationInfo[Piwik_PDFReports_API::PRETTY_DATE_KEY];
			$contents = $notificationInfo[Piwik_PDFReports_API::REPORT_CONTENT_KEY];
			$filename = $notificationInfo[Piwik_PDFReports_API::FILENAME_KEY];
			$additionalFiles = $notificationInfo[Piwik_PDFReports_API::ADDITIONAL_FILES_KEY];

			$reportParameters = $report['parameters'];
			$ftpUri = $reportParameters[self::FTP_URI_PARAMETER];

			preg_match("/ftp:\/\/(.*?):(.*?)@(.*?)(\/.*)/i", $ftpUri, $match);

			$username = $match[1];
			$password = $match[2];
			$host = $match[3];
			$path = $match[4];

			$connection = ftp_connect($host);

			if (!$connection)
			{
				throw new Exception('Connection to FTP ' . $host . ' failed');
			}

			$login = ftp_login($connection, $username, $password);

			if (!$login)
			{
				throw new Exception('Login to FTP ' . $host . ' failed');
			}

			$chpath = ftp_chdir($connection, $path);

			if (!$chpath)
			{
				throw new Exception('Changing to directory ' . $host . ' $path');
			}

			$upload = ftp_put($connection, $filename, PIWIK_USER_PATH . '/tmp/assets/' . $filename, FTP_BINARY);

			if (!$upload)
			{
				throw new Exception('Failed to send ' . $filename . ' to ' . $host);
			}
		}
	}
}
