-----------------------------------
-- contact
-----------------------------------
select a.cite_id, count(c.id) as x
from citation_agents a
left join citations c on a.cite_id=c.id
group by cite_id having x=0;

select o.cite_id, count(c.id) as x
from citation_owners o
left join citations c on o.cite_id=c.id
group by 1 having x=0;

select t.cite_id, count(c.id) as x
from tenants t
left join citations c on t.cite_id=c.id
group by 1 having x=0;

-----------------------------------
-- code_case
-----------------------------------
select c.id              as case_number,
       'citation'        as case_type,
       c.status          as case_status,
       --                as district,
       c.date_writen     as open_date,
       --                as case_description,
       c.compliance_date as closed_date,
       u.fullname        as assigned_to_user
       --                as created_by_user,
       --                as last_update_date,
       --                as last_update_user,
       --                as project_number,
       --                as parent_permit_number,
       --                as parent_plan_number
from citations  c
left join users u on u.id=c.inspector_id;

-----------------------------------
-- code_case_violation
-----------------------------------
select c.id              as violation_number,
       c.id              as case_number,
       v.name            as violation_code,
       c.status          as violation_status,
       c.citation        as violation_priority,
       c.note            as violation_note,
       --                as corrective_action_memo,
       c.date_writen     as citation_date,
       c.compliance_date as compliance_date,
       c.date_complied   as resolved_date
from citations       c
join violation_types v on v.id=c.violation

