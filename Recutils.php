<?php

	//  Recutils : Allow acces to recutils file format and utilities trough PHP.
	//  Copyright (C) 2011 MARTIN Damien
	//
	//  This program is free software: you can redistribute it and/or modify
	//  it under the terms of the GNU General Public License as published by
	//  the Free Software Foundation, either version 3 of the License, or
	//  (at your option) any later version.
	//
	//  This program is distributed in the hope that it will be useful,
	//  but WITHOUT ANY WARRANTY; without even the implied warranty of
	//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	//  GNU General Public License for more details.
	//
	//  You should have received a copy of the GNU General Public License
	//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

	/**
	 * Access rec files using system Recutils tools.
	 * @author MARTIN Damien <damien@martin-damien.fr>
	 * @todo Manage errors.
	 */
	class Recutils
	{

		/**
		 * The file we are working on.
		 */
		private $default_file = Null;

		/**
		 * Path to the binaries
		 * If recutils is compiled and install with `make install` or is part of your distribution
		 * leave it empty. Else add the full path to the binaries and to forget the ending slash.
		 */
		private $binaries_path = "";

		# =================================================================

		/**
		 * Create a Recutils instance.
		 * @param The file we want to work with (if Null you will have to define the file each
		 * time you calla a method).
		 */
		function __construct( $file = Null )
		{
			$this->default_file = $file;
		}

		# -----------------------------------------------------------------

		/**
		 * Select datas through a file.
		 * @arg $file The file where we wants to get datas (Null means that we use file
		 * defined in constructor)
		 * @param $condition Conditions for results to be shown.
		 * @param $result_filter Fields you want to see as output (Null means everything).
		 * @return An array of arrays.
		 * @todo The current implemntation don't handle multiple values for a same field.
		 */
		public function select( $condition = Null,
								$result_filter = Null,
								$record = Null,
								$file = Null )
		{
			if ( $file == Null && $this->default_file == Null )
			{
				die( "<p><b>Recutils.select</b> : No file selected.</p>" );
			}
			else
			{

				// We detect on which file we need to work
				if ( $file != Null )
					$work_with = $file;
				else
					$work_with = $this->default_file;

				// Select the good binary
				$cmd = $this->binaries_path.'recsel';

				// Add conditions if they exist
				if ( $condition != Null )
					$cmd .= " -e \"$condition\"";

				// Add result filter if they exist
				if ( $result_filter != Null )
				{
					$cmd .= " -P $result_filter";
					$attributes_names = explode( ',', $result_filter );
				}

				if ( $record != Null )
					$cmd .= " -t $record ";

				//echo "<p>$cmd $work_with</p>";

				// Execute request
				exec("$cmd $work_with", $array_result);

				// Make sub arrays
				$records = array();
				$current_record = 0;
				$current_attribute = 0;
				foreach ( $array_result as $result_line )
				{

					if ( $result_line == "" )
					{
						$current_record++;
						$current_attribute = 0;
					}
					else
					{

						// Extract attribut name and value if it is needed
						$entry_position = strpos( $result_line, ':' );
						if ( $entry_position !== false )
						{
							$key = substr( $result_line, 0, $entry_position );
							$value = substr( $result_line, $entry_position + 1 );
							$records[$current_record][$key] = trim( $value );
							$current_attribute++;
						}
						else
						{

							// First try to manage multiple values attributs
							/*
							if ( $current_attribute <= count( $attributes_names ) - 1 )
								$key = $attributes_names[$current_attribute];
							else
								$key = $attributes_names[count($attributes_names) - 1];
							*/

							$records[$current_record][$key] = $result_line;
							$current_attribute++;
						}

					}

				}

				return $records;

			}
		}

		# -----------------------------------------------------------------

		/**
		 * Insert a record in a rec file.
		 * @param $data An indexed array with $key => $value.
		 * @param $file The file where we wants to add the record (if Null it will use the
		 * file of the current instance).
		 * @return Nothing for the moment.
		 */
		public function insert( array $datas, $record, $file = Null )
		{
			if ( $file == Null && $this->default_file == Null )
			{
				die( "<p><b>Recutils.select</b> : No file selected.</p>" );
			}
			else
			{

				// We detect on which file we need to work
				if ( $file != Null )
					$work_with = $file;
				else
					$work_with = $this->default_file;

				// Manage datas
				$insert_string = "";
				foreach ( $datas as $key => $value )
				{
					$value = addslashes( $value );
					$insert_string .= " -f $key -v \"$value\" ";
				}

				$cmd = $this->binaries_path.'recins';

				$cmd .= $insert_string;

				$return = exec( "$cmd -t $record $work_with", $out );

			}
		}

		# -----------------------------------------------------------------

		/**
		 * Update a record in a rec file.
		 * @param $datas An indexed array with $key => $value.
		 * @param 
		 */
		public function update( array $datas, $condition, $record, $file = Null )
		{
			if ( $file == Null && $this->default_file == Null )
			{
				die( "<p><b>Recutils</b> : No file selected.</p>" );
			}
			else
			{

				// We detect on which file we need to work
				if ( $file != Null )
					$work_with = $file;
				else
					$work_with = $this->default_file;


				$cmd = $this->binaries_path.'recset';
				$cmd .= " -e '$condition' ";

				// Manage datas
				foreach ( $datas as $key => $value )
				{

					// `Set` only manage 1 modification by action !

					$value = addslashes( $value );
					$update_string = " -f $key -S \"$value\" ";

					$return = exec( "$cmd $update_string -t $record $work_with", $out );

				}

			}
		}

		# -----------------------------------------------------------------

		/**
		 * Delete a record in a rec file.
		 * @arg $condition The conditions for a record to be deleted.
		 * @arg $record The record type where we wants to delete the record.
		 * @arg $comment If true comment the record instead of deleting it.
		 * @arg $file The file where we wants to remove the record (if Null it will use the
		 * file of the current instance).
		 * @return Nothing for the moment.
		 */
		public function delete( $condition, $record = Null, $comment = false, $file = Null )
		{
			if ( $file == Null && $this->default_file == Null )
			{
				die( "<p><b>Recutils.select</b> : No file selected.</p>" );
			}
			else
			{

				// We detect on which file we need to work
				if ( $file != Null )
					$work_with = $file;
				else
					$work_with = $this->default_file;

				$cmd = $this->binaries_path.'recdel';

				if ( $comment )
					$cmd .= " -c ";

				if ( $record != Null )
					$cmd .= " -t $record ";

				$cmd .= " -e \"$condition\"";

				$return = exec( "$cmd $work_with" );

			}

		}

		# -----------------------------------------------------------------

		public static function getVersion()
		{
			
			$cmd = 'recsel --version';
			exec( $cmd, $output );
			
			$first_line = $output[0];

			return substr( $first_line, strrpos( $first_line, ' ' ) );
		}

	}

?>
