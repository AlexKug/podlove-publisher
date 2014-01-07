<?php
namespace Podlove\Modules\EpisodeNumbering;

class Season_List_Table extends \Podlove\List_Table {
	
	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'season',   // singular name of the listed records
		    'plural'    => 'seasons',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}
	

	public function column_number( $season ) {
		$actions = array(
			'edit'   => Settings\Seasons::get_action_link( $season, __( 'Edit', 'podlove' ) ),	
		);

		if( $season->number !== '1' )
			$actions['delete'] = Settings\Seasons::get_action_link( $season, __( 'Delete', 'podlove' ), 'confirm_delete' );
	
		return sprintf( '%1$s %2$s',
		    Settings\Seasons::get_action_link( $season, $season->number ),
		    $this->row_actions( $actions )
		) . '<input type="hidden" class="season_id" value="' . $season->id . '">';
	}

	public function column_mnemonic( $season ) {
		return $season->mnemonic;
	}

	public function column_description( $season ) {
		return $season->description;
	}

	public function get_columns(){
		$columns = array(
			'number' => __( 'Number', 'podlove' ),
			'mnemonic'  => __( 'Mnemonic', 'podlove' ),
			'description'  => __( 'Description', 'podlove' ),
		);
		return $columns;
	}
	
	public function prepare_items() {
		// number of items per page
		$per_page = 10;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		// retrieve data
		$data = \Podlove\Modules\EpisodeNumbering\Model\Season::all( 'ORDER BY number ASC' );
		
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