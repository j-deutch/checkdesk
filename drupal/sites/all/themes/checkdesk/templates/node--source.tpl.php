<section id="node-<?php print $node->nid; ?>" class="default-view-node-<?php print $node->nid; ?> item node-<?php print $node->nid; ?> <?php print $classes; ?>"<?php print $attributes; ?>>
  <article class="source">
    <div class="item-wrapper">
      <section class="source-holder item-content-wrapper">
        <div class="source-content">
          <div class="source-avatar"><?php print render($content['field_image']); ?></div>
          <span class="title"><?php print l($title, 'node/' . $node->nid , array('html' => TRUE)); ?></span>
          <?php if (isset($username_link)): ?>
            <span class="username"><?php print $username_link; ?></span>
          <?php endif; ?>
          
          <?php if(isset($media_description)) : ?>
            <span class="description expandable2x"><?php print $media_description; ?></span>
          <?php endif; ?>

          <?php if (count($source_metadata)) : ?>
            <div class="source-metadata">
              <?php foreach($source_metadata as $k => $metadata) : ?>
                <?php if (isset($content[$metadata])) : ?>
                  <div class="source-metadata-item"><?php print render($content[$metadata]); ?></div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if(isset($source_status)) : ?>
            <div class="media-status media-source-status">
              <?php print $source_status; ?>
            </div>
          <?php endif; ?>
        </div>

      </section>

      <?php print $source_activity; ?>
    </div>
  </article>

  <aside class="report-footer">
    <!-- tag list -->
    <?php if(isset($content['field_tags'])) : ?>
      <?php print render($content['field_tags']); ?>
    <?php endif ?>
  </aside>

  <?php if(isset($references)) : ?>
    <section id="references" class="cd-container cd-container-inline">
      <div class="cd-container-inner">

        <div class="cd-container-header">
          <h2 class="cd-container-header-title">
            <?php print $source_reference_title; ?>
          </h2>
        </div>

        <div class="cd-container-body">
          <?php print $references; ?>
        </div>
      </div>
    </section>
  <?php endif ?>

</section>
