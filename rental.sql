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

-- permit_address
select r.id         as permit_number,
       case when subunit_id is not null then 1 else 0 end as main_address,
       --           as address_type,
       --           as address_type,
       --           as street_number,
       --           as pre_direction,
       --           as street_name,
       --           as street_type,
       --           as post_direction,
       --           as unit_suite_number,
       --           as address_line_3,
       --           as po_box,
       --           as city,
       --           as state_code,
       --           as province,
       --           as zip,
       --           as county_code,
       --           as country_code,
       --           as country_type,
       --           as last_update_date,
       --           as last_update_user
from rental.registr          r
join rental.rental_addresses a on r.id=a.id

-- permit_contact
select r.id              as permit_number,
       n.name_num        as contact_id,
       'agent'           as contact_type,
       0                 as primary_billing_contact
from rental.registr r
join rental.name    n on r.agent=n.name_num;

select id                as permit_number,
       name_num          as contact_id,
       'owner'           as contact_type,
       1                 as primary_billing_contact
from rental.regid_name;

-- permit_inspection
select i.id              as permit_number,
       i.insp_id         as inspection_number
from rental.inspections i;

-- permit_fee
select b.bid             as permit_fee_id,
       b.id              as permit_number,
       --                as fee_type
       (  (b.bul_rate * b.bul_cnt)
       + (b.unit_rate * b.unit_cnt)
       + (b.bath_rate * b.bath_cnt)
       + (b.noshow_rate * b.noshow_cnt)
       + (b.reinsp_rate * b.reinsp_cnt)
       + (b.summary_rate * b.summary_cnt)
       + (b.idl_rate  * b.idl_cnt)
       + b.bhqa_fine
       + b.other_fee
       + b.other_fee2
       - b.credit)       as fee_amount,
       b.issue_date      as fee_date,
       --                as created_by_user,
       --                as input_value,
       --                as fee_note
from rental.reg_bills b

-- payment
select
    --                   as payment_id,
    p.receipt_no         as receipt_number,
    p.rec_from           as payment_method,
    p.check_no           as check_number,
    p.rec_sum            as payment_amount,
    p.rec_date           as payment_date,
    --                   as created_by_user,
    --                   as payment_note
from rental.reg_paid p

-- permit_payment_detail
select b.bid             as permit_fee_id,
       --                as permit_payment_id,

from rental.reg_bills b
join rental.reg_paid  p on b.bid=p.bid

-- ------------------------
-- Inspections
-- ------------------------
select i.insp_id         as inspection_number,
       i.inspection_type as inspection_type,
       i.time_status     as inspection_status,
       --                as create_date,
       --                as requested_for_date,
       --                as scheduled_for_date,
       --                as attempt_number,
       1                 as completed,
       --                as last_update_date,
       --                as last_update_user,
       i.inspected_by    as inspector,
       i.inspection_date as inspected_date_start,
       i.inspection_date as inspected_date_end,
       i.comments        as comment,
       --                as inspection_case_number
from rental.inspections i

select i.insp_id         as inspection_number,
from rental.inspections i
join rental.registr     r on i.id=r.id
