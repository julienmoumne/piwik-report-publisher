<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package ReportPublisher
 */
namespace Piwik\Plugins\ReportPublisher;

use Exception;
use Piwik\Piwik;
use Piwik\Plugins\ScheduledReports\API;
use Piwik\ReportRenderer;
use Piwik\View;

/**
 *
 * @package ReportPublisher
 */
class ReportPublisher extends \Piwik\Plugin
{
    const FTP_TYPE = 'ftp';
    const FTP_URI_PARAMETER = 'ftpUri';

    static private $managedReportTypes = array(
        self::FTP_TYPE => 'plugins/ReportPublisher/images/ftp.png'
    );

    static private $managedReportFormats = array(
        ReportRenderer::PDF_FORMAT => 'plugins/UserSettings/images/plugins/pdf.gif'
    );

    static private $availableParameters = array(
        self::FTP_URI_PARAMETER => true,
    );


    /**
     * @see Piwik_Plugin::getListHooksRegistered
     */
    function getListHooksRegistered()
    {
        return array(
            'ScheduledReports.getReportParameters' => 'getReportParameters',
            'ScheduledReports.validateReportParameters' => 'validateReportParameters',
            'ScheduledReports.getReportMetadata' => 'getReportMetadata',
            'ScheduledReports.getReportTypes' => 'getReportTypes',
            'ScheduledReports.getReportFormats' => 'getReportFormats',
            'ScheduledReports.getRendererInstance' => 'getRendererInstance',
            'ScheduledReports.getReportRecipients' => 'getReportRecipients',
            'ScheduledReports.processReports' => 'processReports',
            'ScheduledReports.allowMultipleReports' => 'allowMultipleReports',
            'ScheduledReports.sendReport' => 'sendReport',
            'Template.reportParametersScheduledReports' => 'template_reportParametersScheduledReports',
        );
    }

    public function getReportTypes(&$reportTypes)
    {
        $reportTypes = array_merge($reportTypes, self::$managedReportTypes);
    }

    public function getReportFormats(&$reportFormats, $reportType)
    {
        if (self::manageEvent($reportType)) {
            $reportFormats = self::$managedReportFormats;
        }
    }

    private static function manageEvent($reportType)
    {
        return in_array($reportType, array_keys(self::$managedReportTypes));
    }

    public function validateReportParameters(&$parameters, $reportType)
    {
        if(self::manageEvent($reportType))
        {
            // TODO validate FTP URI
        }
    }

    public function getReportParameters(&$availableParameters, $reportType)
    {
        if (self::manageEvent($reportType)) {
            $availableParameters = self::$availableParameters;
        }
    }

    public function getReportMetadata(&$reportMetadata, $reportType, $idSite)
    {
        if (self::manageEvent($reportType)) {
            $availableReportMetadata = \Piwik\Plugins\API\API::getInstance()->getReportMetadata($idSite);

            $filteredReportMetadata = array();
            foreach ($availableReportMetadata as $reportMetadata) {
                // removing reports from the API category and MultiSites.getOne
                if (
                    $reportMetadata['category'] == 'API' ||
                    $reportMetadata['category'] == Piwik::translate('General_MultiSitesSummary') && $reportMetadata['name'] == Piwik::translate('General_SingleWebsitesDashboard')
                ) continue;

                $filteredReportMetadata[] = $reportMetadata;
            }

            $reportMetadata = $filteredReportMetadata;
        }
    }

    static public function template_reportParametersScheduledReports(&$out)
    {
        $view = new View('@ReportPublisher/reportParametersReportPublisher');
        $view->reportType = self::FTP_TYPE;
        $out .= $view->render();
    }

    public function getReportRecipients(&$recipients, $reportType, $report)
    {
        if (self::manageEvent($reportType)) {
            $recipients = $report['parameters'][self::FTP_URI_PARAMETER];
        }
    }


    public function allowMultipleReports(&$allowMultipleReports, $reportType)
    {
        if (self::manageEvent($reportType)) {
            $allowMultipleReports = true;
        }
    }

    public function getRendererInstance(&$reportRenderer, $reportType, $outputType, $report)
    {
        if (self::manageEvent($reportType)) {
            $reportFormat = $report['format'];

            $reportRenderer = ReportRenderer::factory($reportFormat);

            if ($reportFormat == ReportRenderer::HTML_FORMAT) {
                $reportRenderer->setRenderImageInline($outputType != API::OUTPUT_SAVE_ON_DISK);
            }
        }
    }

    public function processReports(&$processedReports, $reportType, $outputType, $report)
    {
        if (self::manageEvent($reportType)) {

            foreach ($processedReports as &$processedReport) {
                $metadata = $processedReport['metadata'];

                $processedReport['displayTable'] = true;

                $processedReport['displayGraph'] =
                    \Piwik\SettingsServer::isGdExtensionEnabled()
                    && \Piwik\Plugin\Manager::getInstance()->isPluginActivated('ImageGraph')
                    && !empty($metadata['imageGraphUrl']);

                $processedReport['evolutionGraph'] = true;

                // remove evolution metrics from MultiSites.getAll
                if ($metadata['module'] == 'MultiSites') {
                    $columns = $processedReport['columns'];

                    foreach (\Piwik\Plugins\MultiSites\API::getApiMetrics($enhanced = true) as $metricSettings) {
                        unset($columns[$metricSettings[\Piwik\Plugins\MultiSites\API::METRIC_EVOLUTION_COL_NAME_KEY]]);
                    }

                    $processedReport['metadata'] = $metadata;
                    $processedReport['columns'] = $columns;
                }
            }
        }
    }

    public function sendReport($reportType, $report, $contents, $filename, $prettyDate, $reportSubject, $reportTitle, $additionalFiles)
    {
        if(self::manageEvent($reportType))
        {
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
