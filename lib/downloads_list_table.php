<?php
namespace Podlove;

class Downloads_List_Table extends \Podlove\List_Table {

	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'download',   // singular name of the listed records
		    'plural'    => 'downloads',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function column_episode( $episode ) {
		return $episode['title'];
	}

	public function column_downloads( $episode ) {
		return $episode['downloads'] ? $episode['downloads'] : "–";
	}

	public function column_downloadsMonth( $episode ) {
		return $episode['downloadsMonth'] ? $episode['downloadsMonth'] : "–";
	}

	public function column_downloadsWeek( $episode ) {
		return $episode['downloadsWeek'] ? $episode['downloadsWeek'] : "–";
	}

	public function column_downloadsYesterday( $episode ) {
		return $episode['downloadsYesterday'] ? $episode['downloadsYesterday'] : "–";
	}

	public function column_downloadsToday( $episode ) {
		return $episode['downloadsToday'] ? $episode['downloadsToday'] : "–";
	}

	public function get_columns(){
		return array(
			'episode'            => __( 'Episode', 'podlove' ),
			'downloads'          => __( 'Total Downloads', 'podlove' ),
			'downloadsMonth'     => __( '30 Days', 'podlove' ),
			'downloadsWeek'      => __( '7 Days', 'podlove' ),
			'downloadsYesterday' => __( 'Yesterday', 'podlove' ),
			'downloadsToday'     => __( 'Today', 'podlove' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'episode'            => array('episode', true),
			'downloads'          => array('downloads', true),
			'downloadsMonth'     => array('downloadsMonth', true),
			'downloadsWeek'      => array('downloadsWeek', true),
			'downloadsYesterday' => array('downloadsYesterday', true),
			'downloadsToday'     => array('downloadsToday', true),
		);
	}	

	public function prepare_items() {
		global $wpdb;

		// number of items per page
		$per_page = 20;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby_map = array(
			'episode'            => 'p.post_date',
			'downloads'          => 'downloads',
			'downloadsMonth'     => 'downloadsMonth',
			'downloadsWeek'      => 'downloadsWeek',
			'downloadsYesterday' => 'downloadsYesterday',
			'downloadsToday'     => 'downloadsToday'
		);

		// look for order options
		if( isset($_GET['orderby']) && isset($orderby_map[$_GET['orderby']])  ) {
			$orderby = 'ORDER BY ' . $orderby_map[$_GET['orderby']];
		} else{
			$orderby = 'ORDER BY p.post_date';
		}

		// look how to sort
		if( isset($_GET['order'])  ) {
			$order = strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';
		} else{
			$order = 'DESC';
		}
		
		// retrieve data
		$subSQL = function($start = null, $end = null) {

			$strToMysqlDate = function($s) { return date('Y-m-d', strtotime($s)); };

			if ($start && $end) {
				$timerange = " AND di2.accessed_at BETWEEN '{$strToMysqlDate($start)}' AND '{$strToMysqlDate($end)}'";
			} elseif ($start) {
				$timerange = " AND DATE(di2.accessed_at) = '{$strToMysqlDate($start)}'";
			} else {
				$timerange = "";
			}

			return "
				SELECT
					COUNT(di2.id) downloads
				FROM
					" . Model\MediaFile::table_name() . " mf2
					LEFT JOIN " . Model\DownloadIntent::table_name() . " di2 ON di2.media_file_id = mf2.id
				WHERE
					mf2.episode_id = e.id
					$timerange
			";
		};

		$sql = "
			SELECT
				e.id,
				p.post_title title,
				COUNT(di.id) downloads,
				(" . $subSQL('30 days ago', 'now') . ") downloadsMonth,
				(" . $subSQL('7 days ago', 'now') . ") downloadsWeek,
				(" . $subSQL('1 day ago') . ") downloadsYesterday,
				(" . $subSQL('now') . ") downloadsToday
			FROM
				" . Model\Episode::table_name() . " e
				JOIN " . $wpdb->posts . " p ON e.post_id = p.ID
				JOIN " . Model\MediaFile::table_name() . " mf ON e.id = mf.episode_id
				LEFT JOIN " . Model\DownloadIntent::table_name() . " di ON di.media_file_id = mf.id
			WHERE
				p.post_status IN ('publish', 'private')
			GROUP BY
				e.id
			$orderby $order
		";

		$data = $wpdb->get_results($sql, ARRAY_A);

		// get current page
		$current_page = $this->get_pagenum();
		// get total items
		$total_items = count( $data );
		// extrage page for current page only
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ) , $per_page );
		// add items to table
		$this->items = $data;
		
		// register pagination options & calculations
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}
