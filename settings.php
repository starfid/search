<?php

	$settings = array(

		'timezone' => 'Asia/Jakarta',
		'dictionary_folder' => 'dictionary',

		'database' => array(
			'rdbms'		=> 'mysql',
			'host'		=> '127.0.0.1',
			'port'		=> '3306',
			'username'	=> 'your db username',
			'password'	=> 'your db password',
			'dbname'	=> 'your db name'
		),

		'tables' => array(
			array(
				"name" 			=> "buku",
				"index"			=> array("judul","penulis1","editor"),
				"category"		=> "case when gol_pustaka = 'BK' then 'buku' when gol_pustaka = 'SK' then 'skripsi' when gol_pustaka = 'JU' then 'jurnal' when gol_pustaka = 'LP' then 'laporan' when gol_pustaka = 'AV' then 'multimedia' end",
				"header"		=> "judul",
				"location"		=> "if(gol_pustaka = 'SK',concat('Rak ',penerbit,' &gt; ',kelas),concat('Rak ',kelas))",
				"additional"		=> "concat(penulis1,'. ',if(gol_pustaka = 'SK',concat('Bimbingan ',editor),penerbit),'. ',th_terbit,'. ', kota,'..')",
				"entry"			=> "date(tgl_input)",
				"pubyear"		=> "th_terbit",
				"lang"			=> '(select bhs from bahasa where bahasa.kd_bhs = buku.kd_bhs)',
				"limit"			=> "25",
			),
			array(
				"name" 			=> "jurnal_artikel",
				"index"			=> array("judul","penulis","nama_jurnal"),
				"category"		=> "'jurnal'",
				"header"		=> "judul",
				"location"		=> "concat(nama_jurnal,' &gt; Vol ',volume,' &gt; Nomor ',nomor,' &gt; Tahun ',tahun)",
				"additional"		=> "concat(penulis,'. ',artikel)",
				"entry"			=> "date(tgl_input)",
				"pubyear"		=> "tahun",
				"lang"			=> "'indonesia'",
				"limit"			=> "25",
			),
			array(
				"name" 			=> "jurnal",
				"index"			=> array("judul","subject"),
				"category"		=> "'ebook'",
				"header"		=> "replace(judul,'.pdf','')",
				"location"		=> "vendor",
				"additional"		=> "concat(subject,'. ',tahun)",
				"entry"			=> "tahun",
				"pubyear"		=> "tahun",
				"lang"			=> "'english'",
				"limit"			=> "25",
			),
			array(
				"name" 			=> "anggota",
				"index"			=> array("nama","alamat"),
				"category"		=> "'people'",
				"header"		=> "nama",
				"location"		=> "(select concat(fakultas,' &gt; ',jurusan) from fakultas where fakultas.kd_fakultas = anggota.kd_fakultas)",
				"additional"		=> "alamat",
				"entry"			=> "tgl_daftar",
				"lang"			=> "'indonesia'",
				"pubyear"		=> "angkatan",
				"limit"			=> "25",
			),
			array(
				"name" 			=> "eprint",
				"index"			=> array("nama","title"),
				"category"		=> "'repository'",
				"header"		=> "title",
				"location"		=> "concat('http://example.org/',eprintid)",
				"additional"		=> "abstrak",
				"entry"			=> "date(entry)",
				"pubyear"		=> "tahun",
				"lang"			=> "'indonesia'",
				"limit"			=> "25",
			),
			
			

		),

		'preference' => array(
			'site'			=> array(
				'name'			=> 'Search Engine',
				'desc'			=> 'Indexing multiple tables and sort the result in relevance order start from the top list',
			),
			'campaign'		=> array(
				'title'			=> 'Protect Each Other',
				'desc'			=> 'Love your family and friends, wear a face mask'
			),
			'categories'		=> array(
				'buku' 			=> array(
					'address' 		=> '3rd and 4th Floors. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'book.png'
				),
				'skripsi' 		=> array(
					'address' 		=> '2nd Floor. Thesis Room. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'thesis.png'
				),
				'multimedia' 		=> array(
					'address' 		=> '3rd Floor. Multimedia Room. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'multimedia.png'
				),
				'laporan' 		=> array(
					'address' 		=> '3rd Floor. Serial Room. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'serial.png'
				),
				'jurnal' 		=> array(
					'address' 		=> '3rd Floor. Serial Room. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'serial.png'
				),
				'ebook' 		=> array(
					'address' 		=> '3rd Floor. Serial Room. State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'serial.png'
				),
				'repository' 	=> array(
					'address' 		=> 'Accessible inside State University Library. One Infinite Loop Cupertino. California',
					'map' 			=> 'serial.png'
				),
			),
			'developing'		=> true,
			'minimizeHTML'		=> true,

		)

	);
