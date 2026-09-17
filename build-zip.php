<?php
/**
 * Package WooCommerce Social Publisher using PHP ZipArchive
 * Guarantees standard UNIX forward slashes (/) for all entries so WordPress extracts properly.
 */

$source_dir = __DIR__ . '/woocommerce-social-publisher';
$zip_file   = __DIR__ . '/woocommerce-social-publisher.zip';

if ( file_exists( $zip_file ) ) {
	unlink( $zip_file );
}

$zip = new ZipArchive();
if ( $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
	die( "Failed to create ZIP file.\n" );
}

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::LEAVES_ONLY
);

$exclude_files = [
	'package.ps1',
];

$count = 0;
foreach ( $files as $name => $file ) {
	if ( ! $file->isDir() ) {
		$file_path = $file->getRealPath();
		$relative_path = substr( $file_path, strlen( $source_dir ) + 1 );
		
		// Normalize to forward slashes
		$normalized_relative = str_replace( '\\', '/', $relative_path );

		// Skip exclude list
		if ( in_array( basename( $normalized_relative ), $exclude_files, true ) ) {
			continue;
		}

		$zip_entry_name = 'woocommerce-social-publisher/' . $normalized_relative;
		$zip->addFile( $file_path, $zip_entry_name );
		$count++;
	}
}

$zip->close();

$size_kb = round( filesize( $zip_file ) / 1024, 2 );
echo "Successfully created {$zip_file} with {$count} files ({$size_kb} KB).\n";

// Verify entry names
$verify = new ZipArchive();
$verify->open( $zip_file );
$main_file_index = $verify->locateName( 'woocommerce-social-publisher/woocommerce-social-publisher.php' );
echo "Main plugin file index: " . ( $main_file_index !== false ? "FOUND at index {$main_file_index}" : "NOT FOUND!" ) . "\n";
echo "Total files in archive: " . $verify->numFiles . "\n";
$verify->close();
