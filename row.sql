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
       'row'        as legacy_data_source_name
       --           as isactive
from row.companies n;

---------------------------------
---Business
-- this one can be done by code we need contact_id
-- from contact table
---------------------------------
select n.id               as business_id,
       contact.contact_id as contact_id, -- from contact_id above
       --                 as ownership_type,
       --                 as location_type,
       --                 as business_status,
       --                 as district,
       --                 as open_date,
       --                 as business_description,
       --                 as closed_date,
       --                 as federal_id_number,
       --                 as state_id_number,
       --                 as dba,
       'companies'        as legacy_data_source_name
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
select n.id    as contact_id,
       n.notes as note_text
       --      as note_title,
       --      as note_user,
       --      as note_date
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
       n.work_phone as business_phone,
       --           as home_phone,
       n.cell_phone as mobile_phone,
       --           as other_phone,
       n.fax        as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'contacts'   as legacy_data_source_name
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
select n.id    as contact_id,
       n.notes as note_text,
       --      as note_title,
       --      as note_user,
       --      as note_date
from row.contacts n
where n.notes is not null;
--
------------------------------
---business_contact
------------------------------
select n.compnay_id as business_id,
       n.contact_id as contact_id,
       c.type_id    as contact_type
from row.company_contacts n
join row.contacts         c on n.contact_id=c.id;

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

--------------------------------
-- bond
--------------------------------
select b.id              as bond_id,
       b.bond_num        as bond_number,
       b.type            as bond_type,
       case when b.expire_date<now() then 'expired' else 'not expired yet' end as bond_status,
       --                as issue_date,
       b.expire_date     as expire_date,
       --                as release_date,
       b.amount          as amount,
       --                as global_entity_account_number,
       c.company_id      as obligee_contact_id,
       c.contact_id      as principal_contact_id,
       b.bond_company_id as surety_contact_id
from row.bonds b
left join bond_companies  bc on bc.id=b.bond_company_id -- there are bond_company_id of -1
left join company_contacts c on  c.id=b.company_contact_id
where b.bond_num        is not null
  and b.bond_company_id is not null;

--------------------------------
-- permit
--------------------------------
select r.id              as permit_number,
       'rental'          as permit_type,
       s.status_text     as permit_sub_type,
       case when r.inactive is null then 'active' else 'inactive' end as permit_status,
       --                as district,
       r.registered_date as apply_date,
       --                as permit_description,
       r.permit_issued   as issue_date,
       r.permit_expires  as expire_date,
       --                as last_update_date,
       --                as last_inspection_date,
       --                as valuation,
       --                as square_footage,
       'rentpro'         as legacy_data_source_name
       --                as project_number,
       --                as assigned_to
from row.excavpermits
