select permit.PMPERMITID,
       permit.PERMITNUMBER,
       permit.ISSUEDATE,
       permit.EXPIREDATE,
permitaddress.ADDRESSLINE1 as permit_address,
 owneraddress.ADDRESSLINE1 as owner_address,
 owneraddress.CITY         as owner_city,
 owneraddress.STATE        as owner_state,
 owneraddress.POSTALCODE   as owner_zip,
 agentaddress.ADDRESSLINE1 as agent_address,
 agentaddress.CITY         as agent_city,
 agentaddress.STATE        as agent_state,
 agentaddress.POSTALCODE   as agent_zip
from dbo.PMPERMIT permit
join dbo.CUSTOMSAVERPERMITMANAGEMENT fields on permit.PMPERMITID=fields.ID
join dbo.PMPERMITADDRESS                pla on permit.PMPERMITID=pla.PMPERMITID
join dbo.MAILINGADDRESS       permitaddress on pla.MAILINGADDRESSID=permitaddress.MAILINGADDRESSID
left join (
	select oc.PMPERMITID,
	       oa.ADDRESSLINE1,
		   oa.CITY,
		   oa.STATE,
		   oa.POSTALCODE
	from dbo.PMPERMITCONTACT            oc
	join dbo.LANDMANAGEMENTCONTACTTYPE  ot on oc.LANDMANAGEMENTCONTACTTYPEID=ot.LANDMANAGEMENTCONTACTTYPEID
	join dbo.GLOBALENTITY               oe on oc.GLOBALENTITYID=oe.GLOBALENTITYID
	join dbo.GLOBALENTITYMAILINGADDRESS om on oe.GLOBALENTITYID=om.GLOBALENTITYID
	join dbo.MAILINGADDRESS             oa on om.MAILINGADDRESSID=oa.MAILINGADDRESSID
	where ot.NAME='Owner'
) owneraddress on permit.PMPERMITID=owneraddress.PMPERMITID
left join (
	select ac.PMPERMITID,
	       aa.ADDRESSLINE1,
		   aa.CITY,
		   aa.STATE,
		   aa.POSTALCODE
	from dbo.PMPERMITCONTACT            ac
	join dbo.LANDMANAGEMENTCONTACTTYPE  at on ac.LANDMANAGEMENTCONTACTTYPEID=at.LANDMANAGEMENTCONTACTTYPEID
	join dbo.GLOBALENTITY               ae on ac.GLOBALENTITYID=ae.GLOBALENTITYID
	join dbo.GLOBALENTITYMAILINGADDRESS am on ae.GLOBALENTITYID=am.GLOBALENTITYID
	join dbo.MAILINGADDRESS             aa on am.MAILINGADDRESSID=aa.MAILINGADDRESSID
	where at.NAME='Agent'
) agentaddress on permit.PMPERMITID=agentaddress.PMPERMITID
where permit.PMPERMITID='7450d8ba-dd77-4799-97fb-023e9b0684c0';


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

