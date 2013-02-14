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
 * @package Piwik_ReportPublisher
 */
class Piwik_ReportPublisher_API
{
	static private $instance = null;

	/**
	 * @return Piwik_ReportPublisher_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}
}
