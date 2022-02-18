---
-- the following tables are not used and some of them are
-- backup of the
-- Notes
-- 1- erosion_violations -- not used
-- 2- erosion_citatiosns -- not used
-- 3- planning  -- old data before migration to nov
-- I am using empid (username) for inspector instead of full_name in users table
-- because in the old data the username was used and the full name is not known
-- ------------------------
-- Contacts
-- ------------------------
select n.id   as contact_id,
       --           as company_name
       n.fname       as first_name,
       n.lname       as last_name,
       --           as is_company,
       --           as is_individual,
       --          as email,
       --           as website,
       --           as business_phone,
       --            as home_phone,
       --           as mobile_phone,
       --           as other_phone,
       --           as fax,
       --           as title,
       --           as last_update_date,
       --           as last_update_user,
       'nov'    as legacy_data_source_name
       --           as isactive
from nov.owners n;

-- contact_address
select n.id   as contact_id,
       --           as address_type,
       n.street_num           as street_number,
       n.street_dir           as pre_direction,
       n.street_name           as street_name,
       n.street_type           as street_type,
			 --                   as post_direction,
       n.sud_num||' '||n.sud_type  as unit_suite_number,
       n.rr          as address_line_3,
       n.pobox        as po_box,
       n.city       as city,
       n.state      as state_code,
       --           as province,
       n.zip        as zip,
       --           as county_code,
       --           as country_code,
       --           as country_type,
       --           as last_update_date,
       --           as last_update_user
from nov.owners n;

-----------------------------------
-- code_case
-----------------------------------
select c.id              as case_number,
       'nov'        as case_type,
       s.name          as case_status,
       --                as district,
       c.date_written     as open_date,
       --                as case_description,
       c.compliance_date as closed_date,
       u.empid        as assigned_to_user
       --                as created_by_user,
       --                as last_update_date,
       --                as last_update_user,
       --                as project_number,
       --                as parent_permit_number,
       --                as parent_plan_number
from nov.citations  c
join nov.statuses s on c.status_id=s.id
left join nov.users u on u.id=c.inspector_id;

-----------------------------------
-- code_case_violation
-----------------------------------
select c.id              as violation_number,
       c.id              as case_number,
       v.name            as violation_code,
       s.name          as violation_status,
       c.citation        as violation_priority,
       c.note            as violation_note,
       --                as corrective_action_memo,
       c.date_writen     as citation_date,
       c.compliance_date as compliance_date,
       c.date_complied   as resolved_date
from nov.citations       c
join nov.statuses s on c.status_id=s.id
join nov.violation_types v on v.id=c.violation_id

-----------------------------------
---code_case_activity
-----------------------------------
select l.id as case_number,
       l.cite_id as activity_type, 
       a.name as action,
       l.action_by as activity_user,
       l.activity_date as activity_date
from nov.legal_actions l
join nov.actions       a on a.id=l.action_id";

-------------------------------------------------
--- invoices and payments were not handled in nov directly
--- probably they used another payments app or through controller
---------------------------------------------------

