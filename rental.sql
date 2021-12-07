-- ------------------------
-- Contacts
-- ------------------------
-- contact
select n.name_num   as contact_id,
       --           as company_name
       n.name       as first_name,
       --           as last_name,
       --           as is_company,
       --           as is_individual,
       n.email      as email,
       --           as website,
       n.phone_work as business_phone,
       n.phone_home as home_phone,
       --           as mobile_phone,
       --           as other_phone,
       --           as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'rentpro'    as legacy_data_source_name
       --           as isactive
from rental.name n;

-- contact_address
select n.name_num   as contact_id,
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
from rental.name n;

-- contact_note
select n.name_num   as contact_id,
       n.notes      as note_text
       --           as note_title,
       --           as note_user,
       --           as note_date
from rental.name n
where n.notes is not null;
       

-- ------------------------
-- Permits
-- ------------------------
-- permits
select r.id              as permit_number,
       'rental'          as permit_type,
       s.status_text     as permit_sub_type,
       case when r.inactive='Y' then 'inactive' else 'active' end as permit_status,
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
from rental.registr r
join rental.prop_status s on r.property_status=s.status;
