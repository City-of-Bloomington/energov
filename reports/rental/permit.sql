select permit.PMPERMITID,
       permit.PERMITNUMBER,
       format(permit.ISSUEDATE,          'd') as issuedate,
       format(permit.EXPIREDATE,         'd') as expiredate,
       format(permit.lastinspectiondate, 'd') as lastinspectiondate,
       concat_ws(' ', u.fname, u.lname)       as inspector,
permitaddress.ADDRESSLINE1 as permit_address,
 owner.name         as owner_name,
 owner.ADDRESSLINE1 as owner_address,
 owner.CITY         as owner_city,
 owner.STATE        as owner_state,
 owner.POSTALCODE   as owner_zip,
 agent.name         as agent_name,
 agent.ADDRESSLINE1 as agent_address,
 agent.CITY         as agent_city,
 agent.STATE        as agent_state,
 agent.POSTALCODE   as agent_zip
from PMPERMIT permit
join CUSTOMSAVERPERMITMANAGEMENT fields on permit.PMPERMITID=fields.ID
join PMPERMITADDRESS                pla on permit.PMPERMITID=pla.PMPERMITID
join MAILINGADDRESS       permitaddress on pla.MAILINGADDRESSID=permitaddress.MAILINGADDRESSID
join iminspectioncase                ic on permit.pmpermitid=ic.linkid
join iminspection                     i  on i.iminspectioncaseid=ic.iminspectioncaseid and i.actualenddate=(select max(x.actualenddate) from iminspection x where x.iminspectioncaseid=ic.iminspectioncaseid)
join iminspectorref ir on ir.inspectionid=i.iminspectionid and ir.bprimary=1
join users u on ir.userid =u.suserguid
left join (
    select oc.PMPERMITID,
           case oe.iscompany when 1
            then oe.globalentityname
            else concat_ws(' ', oe.firstname, oe.middlename, oe.lastname)
           end as name,
           oa.ADDRESSLINE1,
           oa.CITY,
           oa.STATE,
           oa.POSTALCODE
    from PMPERMITCONTACT            oc
    join LANDMANAGEMENTCONTACTTYPE  ot on oc.LANDMANAGEMENTCONTACTTYPEID=ot.LANDMANAGEMENTCONTACTTYPEID
    join GLOBALENTITY               oe on oc.GLOBALENTITYID=oe.GLOBALENTITYID
    join GLOBALENTITYMAILINGADDRESS om on oe.GLOBALENTITYID=om.GLOBALENTITYID
    join MAILINGADDRESS             oa on om.MAILINGADDRESSID=oa.MAILINGADDRESSID
    where ot.NAME='Owner'
) owner on permit.PMPERMITID=owner.PMPERMITID
left join (
    select ac.PMPERMITID,
           case ae.iscompany when 1
            then ae.globalentityname
            else concat_ws(' ', ae.firstname, ae.middlename, ae.lastname)
           end as name,
           aa.ADDRESSLINE1,
           aa.CITY,
           aa.STATE,
           aa.POSTALCODE
    from PMPERMITCONTACT            ac
    join LANDMANAGEMENTCONTACTTYPE  at on ac.LANDMANAGEMENTCONTACTTYPEID=at.LANDMANAGEMENTCONTACTTYPEID
    join GLOBALENTITY               ae on ac.GLOBALENTITYID=ae.GLOBALENTITYID
    join GLOBALENTITYMAILINGADDRESS am on ae.GLOBALENTITYID=am.GLOBALENTITYID
    join MAILINGADDRESS             aa on am.MAILINGADDRESSID=aa.MAILINGADDRESSID
    where at.NAME='Representative'
) agent on permit.PMPERMITID=agent.PMPERMITID
where permit.PMPERMITID='{?@PMPERMITID}';

select min(case displayname when 'Structure Identifier' then value end) as structure,
       min(case displayname when 'Units'                then value end) as units,
       min(case displayname when 'Bedrooms'             then value end) as bedrooms,
       min(case displayname when 'Occupancy'            then value end) as occupancy
from (
    select v.rownumber,
           r.displayname,
           v.value
    from customfieldtable t
    join customfieldtablecolumnref  r on r.customfieldtableid=t.customfieldtableid
    left join (
        select rownumber, objectid, cftablecolumnrefid, stringvalue as value from customsavertblcol_str
        union
        select rownumber, objectid, cftablecolumnrefid,    intvalue as value from customsavertblcol_int
    ) v on v.cftablecolumnrefid=r.customfieldtablecolumnrefid
    where t.name='Rental Property Information'
      and v.objectid='8679E41F-88CF-4F61-B477-E90C83D84298'
) as d
group  by rownumber;


select  wfs.name as workflow_step,
       wfas.name as action_step,
       s.statusname,
       i.actualenddate
from dbo.pmpermitwfstep       wfs
join dbo.pmpermitwfactionstep wfas on wfas.pmpermitwfstepid=wfs.pmpermitwfstepid
join dbo.iminspectionactref   ia   on ia.objectid=wfas.pmpermitwfactionstepid
join dbo.iminspection         i    on i.iminspectionid=ia.iminspectionid
join dbo.iminspectionstatus   s    on i.iminspectionstatusid=s.iminspectionstatusid
where wfs.pmpermitid='7450d8ba-dd77-4799-97fb-023e9b0684c0';

