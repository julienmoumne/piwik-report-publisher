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
class Piwik_ReportPublisher_Controller extends Piwik_Controller_Admin
{

	function index()
	{
		Piwik::checkUserIsNotAnonymous();

		$view = Piwik_View::factory('Settings');


		$reportPublisherAPI = Piwik_ReportPublisher_API::getInstance();
		$view->ftpUri = $reportPublisherAPI->getFtpUri();

		$this->setBasicVariablesView($view);

		$view->menu = Piwik_GetAdminMenu();
		echo $view->render();
	}
}
