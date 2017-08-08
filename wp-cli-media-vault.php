<?php
/*
Plugin Name: Media Vault - WP CLI
Version: 1.0.0
Description: Take one media and protect it
Author: Be API
*/

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Allow to protect and unprotect medias from library
 *
 */
class Media_Vault_CLI extends \WP_CLI_Command {

	/**
	 * Protect media with media vault.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The Attachment ID.
	 *
	 * [--all]
	 * : Make all the attachments of the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp media-vault protect 10
	 *     wp media-vault protect --all
	 *
	 */
	public function protect( $args, $assoc_args ) {
		if ( ! is_plugin_active( 'media-vault/_mediavault.php' ) ) {
			\WP_CLI::error( 'Media Vault should be activated.' );
		}

		/**
		 * File given
		 */
		if ( $args[0] ) {
			$attachment_id = (int) $args[0];
			$attachment    = get_post( $attachment_id );

			if ( get_post_type( $attachment_id ) !== 'attachment' ) {
				\WP_CLI::error( 'The file must be an attachment.' );
			}

			$moved = $this->protect_media( $attachment );

			if ( is_wp_error( $moved ) ) {
				\WP_CLI::error( $moved->get_error_message() );
			}
			\WP_CLI::success( sprintf( 'Attachment %d protected', $attachment_id ) );

			return;
		}

		if ( ! $assoc_args['all'] ) {
			\WP_CLI::error( 'You need to specify an attachment id or --all param to protect every file.' );
		}

		$attachments = get_posts( [ 'post_type'     => 'attachment',
		                            'post_status'   => 'any',
		                            'nopaging'      => true,
		                            'no_found_rows' => true,
		] );

		if ( empty( $attachments ) ) {
			\WP_CLI::error( 'No media into your library.' );
		}

		$count = count( $attachments );
		\WP_CLI::warning( sprintf( '%d medias.', $count ) );
		$progress = \WP_CLI\Utils\make_progress_bar( 'Protecting medias', $count );
		foreach ( $attachments as $attachment ) {
			$moved = $this->protect_media( $attachment );

			if ( is_wp_error( $moved ) ) {
				\WP_CLI::error( $moved->get_error_message(), false );
				$progress->tick();
				continue;
			}

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Unprotect media with media vault.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The Attachment ID.
	 *
	 * [--all]
	 * : Make all the attachments of the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp media-vault unprotect 10
	 *     wp media-vault unprotect --all
	 *
	 */
	public function unprotect( $args, $assoc_args ) {
		if ( ! is_plugin_active( 'media-vault/_mediavault.php' ) ) {
			\WP_CLI::error( 'Media Vault should be activated.' );

			return;
		}

		/**
		 * File given
		 */
		if ( $args[0] ) {
			$attachment_id = (int) $args[0];
			$attachment    = get_post( $attachment_id );

			if ( get_post_type( $attachment_id ) !== 'attachment' ) {
				\WP_CLI::error( 'The file must be an attachment.' );
			}

			$moved = $this->unprotect_media( $attachment );

			if ( is_wp_error( $moved ) ) {
				\WP_CLI::error( $moved->get_error_message() );
			}
			\WP_CLI::success( sprintf( 'Attachment %d unprotected', $attachment_id ) );

			return;
		}

		if ( ! $assoc_args['all'] ) {
			\WP_CLI::error( 'You need to specify an attachment id or --all param to unprotect every file.' );
		}

		$attachments = get_posts( [ 'post_type'     => 'attachment',
		                            'post_status'   => 'any',
		                            'nopaging'      => true,
		                            'no_found_rows' => true,
		] );

		if ( empty( $attachments ) ) {
			\WP_CLI::error( 'No media into your library.' );
		}

		$count = count( $attachments );
		\WP_CLI::warning( sprintf( '%d medias.', $count ) );
		$progress = \WP_CLI\Utils\make_progress_bar( 'Unprotecting medias', $count );
		foreach ( $attachments as $attachment ) {
			$moved = $this->protect_media( $attachment );

			if ( is_wp_error( $moved ) ) {
				\WP_CLI::error( $moved->get_error_message(), false );
				$progress->tick();
				continue;
			}

			$progress->tick();
		}

		$progress->finish();
	}


	/**
	 * Protect one attachment
	 *
	 * @param WP_Post $attachment
	 *
	 * @return bool|\WP_Error
	 */
	private function protect_media( WP_Post $attachment ) {
		$move = mgjp_mv_move_attachment_to_protected( $attachment->ID );

		if ( is_wp_error( $move ) ) {
			return $move;
		}

		/**
		 * Update the permissions
		 */
		$permissions = mgjp_mv_get_the_permissions();
		if ( 'default' == $attachment->mgjp_mv_permission_select || ! isset( $permissions[ $attachment->mgjp_mv_permission_select ] ) ) {
			delete_post_meta( $attachment->ID, '_mgjp_mv_permission' );
		} else {
			update_post_meta( $attachment->ID, '_mgjp_mv_permission', $attachment['mgjp_mv_permission_select'] );
		}

		return true;
	}

	/**
	 * Protect one attachment
	 *
	 * @param WP_Post $attachment
	 *
	 * @return bool|\WP_Error
	 */
	private function unprotect_media( WP_Post $attachment ) {
		$move = mgjp_mv_move_attachment_from_protected( $attachment->ID );

		if ( is_wp_error( $move ) ) {
			return $move;
		}

		/**
		 * Update the permissions
		 */
		delete_post_meta( $attachment->ID, '_mgjp_mv_permission' );

		return true;
	}

}

\WP_CLI::add_command( 'media-vault', '\Media_Vault_CLI' );
