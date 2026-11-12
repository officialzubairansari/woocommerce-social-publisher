<?php
/**
 * Package WooCommerce Social Publisher using PHP ZipArchive
 * Guarantees standard UNIX forward slashes (/) for all entries so WordPress extracts properly.
 */

$source_dir = __DIR__;
$zip_file   = __DIR__ . '/woocommerce-social-publisher.zip';

if ( file_exists( $zip_file ) ) {
	unlink( $zip_file );
}

$zip = new ZipArchive();
if ( $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
	fwrite( STDERR, "Failed to create ZIP file.\n" );
	exit( 1 );
}

$included_files = [ 'woocommerce-social-publisher.php', 'uninstall.php', 'README.md' ];
$included_dirs  = [ 'admin', 'assets', 'includes', 'languages' ];
$files = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		function ( $file ) use ( $source_dir, $included_files, $included_dirs ) {
			if ( 0 === strpos( $file->getFilename(), '.' ) ) {
				return false;
			}
			if ( $file->getPath() === $source_dir ) {
				return in_array( $file->getFilename(), $file->isDir() ? $included_dirs : $included_files, true );
			}
			return true;
		}
	),
	RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ( $files as $name => $file ) {
	if ( ! $file->isDir() ) {
		$file_path = $file->getRealPath();
		$relative_path = substr( $file_path, strlen( $source_dir ) + 1 );
		
		// Normalize to forward slashes
		$normalized_relative = str_replace( '\\', '/', $relative_path );

		$zip_entry_name = 'woocommerce-social-publisher/' . $normalized_relative;
		if ( ! $zip->addFile( $file_path, $zip_entry_name ) ) {
			fwrite( STDERR, "Failed to add {$normalized_relative}.\n" );
			exit( 1 );
		}
		$count++;
	}
}

if ( ! $zip->close() ) {
	fwrite( STDERR, "Failed to finish ZIP file.\n" );
	exit( 1 );
}

$size_kb = round( filesize( $zip_file ) / 1024, 2 );
echo "Successfully created {$zip_file} with {$count} files ({$size_kb} KB).\n";

// Verify entry names
$verify = new ZipArchive();
$verify->open( $zip_file );
$main_file_index = $verify->locateName( 'woocommerce-social-publisher/woocommerce-social-publisher.php' );
echo "Main plugin file index: " . ( $main_file_index !== false ? "FOUND at index {$main_file_index}" : "NOT FOUND!" ) . "\n";
echo "Total files in archive: " . $verify->numFiles . "\n";
$verify->close();
if ( false === $main_file_index ) {
	exit( 1 );
}
