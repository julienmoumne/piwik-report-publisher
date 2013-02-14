<script>
	$(function() {ldelim}
		resetReportParametersFunctions ['{$reportType}'] =
				function () {ldelim}

					var	reportParameters = {ldelim}
							'ftpUri' : [],
						{rdelim};

					updateReportParametersFunctions['{$reportType}'](reportParameters);
				{rdelim};

		updateReportParametersFunctions['{$reportType}'] =
				function (reportParameters) {ldelim}

					if(reportParameters == null) return;

					$('#ftpUri').val(reportParameters.ftpUri);
				{rdelim};

		getReportParametersFunctions['{$reportType}'] =
				function () {ldelim}

					var parameters = Object();

					parameters.ftpUri = $('#ftpUri').val();

					return parameters;
				{rdelim};
	{rdelim});
</script>

<tr class='{$reportType}'>
	<td class="first">
		{'ReportPublisher_FtpReport_FtpUri'|translate}
	</td>
	<td>
		<input id='ftpUri' size='75'/>
	</td>
</tr>
