-- ------------------------
-- Contacts
-- ------------------------
-- contact
select n.id         as contact_id,
       a.name       as company_name
       --           as first_name,
       --           as last_name,
       'Y'          as is_company,
       --           as is_individual,
       --           as email,
       n.website    as website,
       n.phone      as business_phone,
       n.phone_home as home_phone,
       --           as mobile_phone,
       --           as other_phone,
       --           as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'row'    as legacy_data_source_name
       --           as isactive
from row.companies n;

---------------------------------
---Business
-- this one can be done by code we need contact_id
-- from contact table
---------------------------------
select n.id         as business_id,
			 contact.contact_id         as contact_id, -- from contact_id above
			 --           as ownership_type,
			 --           as location_type,
			 --           as business_status,
			 --           as district,
			 --           as open_date,
			 --           as business_description,
			 --           as closed_date,
			 --           as federal_id_number,
			 --           as state_id_number,
			 --           as dba,
			 'companies' as legacy_data_source_name
from row.companies n;

-- contact_address
select n.id         as contact_id,
       n.address,
       --           as address_type,
       --           as street_number,
       --           as pre_direction,
       --           as street_name,
       --           as street_type,
       --           as post_direction,
       --           as unit_suite_number,
       --           as address_line_3,
       --           as po_box,
       n.city       as city,
       n.state      as state_code,
       --           as province,
       n.zip        as zip,
       --           as county_code,
       --           as country_code,
       --           as country_type,
       --           as last_update_date,
       --           as last_update_user
from row.companies n;

-- contact_note
select n.id   as contact_id,
       n.notes      as note_text
       --           as note_title,
       --           as note_user,
       --           as note_date
from row.companies n
where n.notes is not null;
--
-- ------------------------
-- Contacts
-- ------------------------
-- contact
select n.id         as contact_id,
       --           as company_name
       n.fname      as first_name,
       n.lname      as last_name,
       --           as is_company,
       --           as is_individual,
       n.email      as email,
       n.website    as website,
       n.work_phone      as business_phone,
       --           as home_phone,
       n.cell_phone           as mobile_phone,
       --           as other_phone,
       n.fax           as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'contacts'    as legacy_data_source_name
       --           as isactive
from row.contacts n;

-- contact_address
select n.id         as contact_id,
       n.address,
       --           as address_type,
       --           as street_number,
       --           as pre_direction,
       --           as street_name,
       --           as street_type,
       --           as post_direction,
       --           as unit_suite_number,
       --           as address_line_3,
       --           as po_box,
       n.city       as city,
       n.state      as state_code,
       --           as province,
       n.zip        as zip,
       --           as county_code,
       --           as country_code,
       --           as country_type,
       --           as last_update_date,
       --           as last_update_user
from row.contacts n;

-- contact_note
select n.id   as contact_id,
       n.notes      as note_text,
       --           as note_title,
       --           as note_user,
       --           as note_date
from row.contacts n
where n.notes is not null;
--
------------------------------
---business_contact
------------------------------
select n.compnay_id as business_id,
       n.contact_id as contact_id,
			 c.type_id    as contact_type
from row.company_contacts n,row.contacts c
where n.contact_id=c.id
--
--------------------------------
-- Contact for bond_companies
-- bond_companies has no contacts
--------------------------------
select n.id         as contact_id,
       n.name       as company_name
       --           as first_name,
       --           as last_name,
       'Y'          as is_company,
       --           as is_individual,
       --           as email,
       --           as website,
       --           as business_phone,
       --           as home_phone,
       --           as mobile_phone,
       --           as other_phone,
       --           as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'bond_compnaies'    as legacy_data_source_name,
       --           as isactive
from row.bond_companies n;










