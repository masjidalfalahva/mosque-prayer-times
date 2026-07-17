<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a DOCX file and return all table rows as arrays of cell text.
 *
 * @param string $file_path Absolute path to the uploaded DOCX file.
 *
 * @return array|WP_Error
 */
function mapt_read_docx_table_rows( $file_path ) {

	if ( ! file_exists( $file_path ) ) {
		return new WP_Error(
			'mapt_missing_docx',
			'The uploaded Word document could not be found.'
		);
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error(
			'mapt_zip_missing',
			'Your server does not have the PHP ZipArchive extension enabled.'
		);
	}

	$zip = new ZipArchive();

	$opened = $zip->open( $file_path );

	if ( true !== $opened ) {
		return new WP_Error(
			'mapt_docx_open_failed',
			'The Word document could not be opened.'
		);
	}

	$document_xml = $zip->getFromName( 'word/document.xml' );

	$zip->close();

	if ( false === $document_xml ) {
		return new WP_Error(
			'mapt_document_xml_missing',
			'The Word document does not contain readable document XML.'
		);
	}

	libxml_use_internal_errors( true );

	$xml = simplexml_load_string( $document_xml );

	if ( false === $xml ) {
		libxml_clear_errors();

		return new WP_Error(
			'mapt_invalid_document_xml',
			'The Word document XML could not be read.'
		);
	}

	$namespaces = $xml->getNamespaces( true );

	if ( empty( $namespaces['w'] ) ) {
		return new WP_Error(
			'mapt_word_namespace_missing',
			'The Word document format was not recognized.'
		);
	}

	$xml->registerXPathNamespace( 'w', $namespaces['w'] );

	$table_rows = $xml->xpath( '//w:tbl/w:tr' );

	if ( empty( $table_rows ) ) {
		return new WP_Error(
			'mapt_no_table_rows',
			'No table rows were found in the Word document.'
		);
	}

	$rows = array();

	foreach ( $table_rows as $table_row ) {

		$table_row->registerXPathNamespace( 'w', $namespaces['w'] );

		$cells = $table_row->xpath( './w:tc' );

		if ( empty( $cells ) ) {
			continue;
		}

		$row = array();

		foreach ( $cells as $cell ) {

			$cell->registerXPathNamespace( 'w', $namespaces['w'] );

			$text_nodes = $cell->xpath( './/w:t' );

			$cell_parts = array();

			if ( ! empty( $text_nodes ) ) {
				foreach ( $text_nodes as $text_node ) {
					$cell_parts[] = (string) $text_node;
				}
			}

			$cell_text = implode( ' ', $cell_parts );

			$cell_text = preg_replace(
				'/\s+/',
				' ',
				$cell_text
			);

			$row[] = trim( $cell_text );
		}

		if ( ! empty( array_filter( $row ) ) ) {
			$rows[] = $row;
		}
	}

	return $rows;
}
