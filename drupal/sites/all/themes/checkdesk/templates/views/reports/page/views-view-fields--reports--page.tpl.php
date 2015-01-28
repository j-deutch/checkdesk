<?php
	// determine what kind of media it is
	$url = $fields['source_url']->raw;
	$url = parse_url($url);
    $media_host_class = isset($url['host']) ? str_replace('.', '_', $url['host']) : '';
?>
<div class="report-row-container <?php print $media_type_class; ?> <?php print $media_host_class; ?>" id="report-<?php print $fields['nid']->raw; ?>">
	<?php if ($report_published) { ?>
		<div class="report-published" title="<?php print $report_published; ?>">
			<span><?php print $report_published; ?></span>
		</div>
	<?php } ?>
	<div class="report-content">
    <?php print $fields['field_link']->content; ?>
	</div>
	<?php if($fields['field_rating']->content != 'Not Applicable') { ?>
		<?php
			$status_class = str_replace(' ', '_', strtolower($fields['field_rating']->content));
		?>
		<div class="report-status <?php print $status_class; ?>">
			<span><?php print $name_i18n; ?></span>
		</div>
	<?php } ?>
	<div class="report-detail-link">
	   <?php print l(t('Details'), 'node/'. $fields['nid']->raw); ?>
	</div>
</div> 
