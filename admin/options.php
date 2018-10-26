<div class="wrap">

	<h2><?php esc_html_e( 'Cloudinary Options', 'cloudinary' ); ?></h2>

	<div class="card">
		<form method="post" action="">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="cloudinary_cloud_name"><?php esc_html_e( 'Cloud Name', 'cloudinary' ); ?></label></th>
						<td>
							<input name="cloudinary_cloud_name" id="cloudinary_cloud_name" value="<?php echo esc_html( get_option( 'cloudinary_cloud_name' ) ); ?>" class="regular-text" type="text">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cloudinary_auto_mapping_folder"><?php esc_html_e( 'Auto Mapping Folder', 'cloudinary' ); ?></label></th>
						<td>
							<input name="cloudinary_auto_mapping_folder" id="cloudinary_auto_mapping_folder" value="<?php echo esc_html( get_option( 'cloudinary_auto_mapping_folder' ) ); ?>" class="regular-text" type="text">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cloudinary_default_hard_crop"><?php esc_html_e( 'Default Hard Crop', 'cloudinary' ); ?></label></th>
						<td>
							<select name="cloudinary_default_hard_crop" id="cloudinary_default_hard_crop">
								<option value="fill"
									<?php if ( 'fill' === get_option( 'cloudinary_default_hard_crop' ) ) : ?>
										selected="selected"
									<?php endif; ?>>fill</option>
								<option value="fit"
									<?php if ( 'fit' === get_option( 'cloudinary_default_hard_crop' ) ) : ?>
										selected="selected"
									<?php endif; ?>>fit</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cloudinary_default_soft_crop"><?php esc_html_e( 'Default Soft Crop', 'cloudinary' ); ?></label></th>
						<td>
							<select name="cloudinary_default_soft_crop" id="cloudinary_default_soft_crop">
								<option value="fit"
									<?php if ( 'fit' === get_option( 'cloudinary_default_soft_crop' ) ) : ?>
										selected="selected"
									<?php endif; ?>>fit</option>
								<option value="fill"
									<?php if ( 'fill' === get_option( 'cloudinary_default_soft_crop' ) ) : ?>
										selected="selected"
									<?php endif; ?>>fill</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cloudinary_urls"><?php esc_html_e( 'URLs', 'cloudinary' ); ?></label></th>
						<td>
							<textarea name="cloudinary_urls" id="cloudinary_urls" class="large-text" style="height: 100px;"><?php echo ! empty( get_option( 'cloudinary_urls' ) ) ? esc_html( get_option( 'cloudinary_urls' ) ) : 'https://res.cloudinary.com'; ?></textarea>
							<p class="description"><?php esc_html_e( 'Add one per line.', 'cloudinary' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cloudinary_content_images"><?php esc_html_e( 'Content Images', 'cloudinary' ); ?></label></th>
						<td>
							<input name="cloudinary_content_images" id="cloudinary_content_images" type="checkbox" value="1"
								<?php if ( '1' === get_option( 'cloudinary_content_images' ) ) : ?>
									checked="checked"
								<?php endif; ?>>
							<p class="description"><?php esc_html_e( 'Automatically use Cloudinary for all images?', 'cloudinary' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'cloudinary_options', 'cloudinary_nonce' ); ?>
			<p class="submit"><input class="button-primary" value="<?php esc_html_e( 'Save', 'cloudinary' ); ?>" type="submit"></p>
		</form>
	</div> <!-- .card -->

</div> <!-- .wrap -->
