<?php

	$settings = array(

		'timezone' => 'Asia/Jakarta',
		'dictionary_folder' => 'dictionary',

		'database' => array(
			'rdbms'		=> 'mysql',
			'host'		=> '127.0.0.1',
			'port'		=> '3306',
			'username'	=> 'root',
			'password'	=> 'password',
			'dbname'	=> 'database name'
		),

		'tables' => array(

			array(
				"name" 			=> "buku",
				"index"			=> array("judul","penulis1"),
				"category"		=> "case when gol_pustaka = 'BK' then 'buku' when gol_pustaka = 'SK' then 'skripsi' when gol_pustaka = 'AV' then 'multimedia' end",
				"header"		=> "judul",
				"location"		=> "kelas",
				"additional"		=> "concat(penulis1,'. ',penerbit,'. ',th_terbit,'. ', kota,'..')",
				"entry"			=> "date(tgl_input)",
				"limit"			=> "25",
			),
			
			array(
				"name" 			=> "jurnal_artikel",
				"index"			=> array("judul","penulis"),
				"category"		=> "'jurnal'",
				"header"		=> "judul",
				"location"		=> "kelas",
				"additional"		=> "concat(penulis,'. ',artikel)",
				"entry"			=> "date(tgl_input)",
				"limit"			=> "25",
			),

		),

		'preference' => array(
			'site'		=> array(
				'name'			=> 'Lorem Ipsum Dolor Sit Amet',
				'desc'			=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua',
			),
			'campaign'	=> array(
				'title'			=> 'Excepteur Sint',
				'desc'			=> 'Duis aute irure dolor in reprehenderit in voluptate'
			),


			'debug'		=> true,
			'prefix' 	=> array(
				'buku' 			=> array('buku','definisi','arti','pengertian'),
				'skripsi' 		=> array('skripsi','bimbingan','pdf','fakultas','prodi','jurusan'),
				'multimedia'		=> array('kaset','cd','dvd'),
				'jurnal'		=> array('jurnal'),
				'ebook'			=> array('ebook')
			)
		)

	);
