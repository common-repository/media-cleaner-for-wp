<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h2>Media Cleaner Settings Page</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>All Media Files (including thumbnails)</label></th>
                <?php $all_uploads_images = mcfwp_get_all_images_for_uploads();$all_images_for_uploads_original = mcfwp_get_all_images_without_sizes($all_uploads_images);?>
                <td><?php echo esc_html(count($all_uploads_images));?> (<?php echo esc_html(mcfwp_sum_image_sizes($all_uploads_images));?>Mb)</td>
            </tr>
            <tr>
                <th scope="row"><label>Uploaded Original Media Files</label></th>
                <?php $all_images_for_uploads_original = mcfwp_get_all_images_without_sizes($all_uploads_images);?>
                <td><?php echo esc_html(count($all_images_for_uploads_original));?></td>
            </tr>
            <tr class="hide-if-no-js site-icon-section">
                <th scope="row">Unused Media Files</th>
                <?php $all_unused_images = get_option('mcfwp_get_all_unused_images', false);?>
                <td class="unused-images"><?php echo esc_html(($all_unused_images) ? count($all_unused_images) : 0);?> <?php //mcfwp_dump($all_unused_images);?></td>
            </tr>
            <tr>
                <th scope="row"><label>Actions</label></th>
                <td>
                    <a href="#" class="button button-primary cu_scan">Scan</a>
                    <div class="spinner" style="float: unset;"></div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label></label></th>
                <td><hr/></td>
            </tr>
            <tr class="images-wrapper-tr">
                <th scope="row"><label></label></th>
                <td>
                    <input type="checkbox" class="cu_checked">
                    <a href="" class="button button-primary cu_delete">Delete <span>0</span></a>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Unused Media Files</label></th>
                <td>
                    <div class="counter">

                    </div>
                    <div class="wrapper images-wrapper" data-loading="0">

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>