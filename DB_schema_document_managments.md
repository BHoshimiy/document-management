DB

Tables:
	- users table
		id
		username:unique
		password
		status
		role[admin,client,modirator]

	- munes table
		id
		name(ru,en)
		slug(en)
		order

	- categories table
		id
		name(ru,en)
		slug(en)
		order
		is_default
		document_folder_id:nullable -> fk -> document_folders

	- document_folders table
		id
		menu_id 					-> fk -> munes
		name(ru,en)
		code
		slug(en)
		order

	- documents table
		id
		company_id					-> fk -> companies 
		category_id 				-> fk -> categories
		document_folder_id 			-> fk -> document_folders
		name
		path

	- companies table 
		id
		user_id 					-> fk -> users
		name
		inn
		address
		logo

	- company_certificates table
		id
		certificate
		compony_id 					-> fk -> companies
		date_from:date
		date_to:date

	- company_application_forms
		id
		company_id 					-> fk -> companies 
		. . .






