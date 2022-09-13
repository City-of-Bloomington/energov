select f.cafeeid           as fee_id,
       f.name              as fee,
       f.arfeecode         as charge_code,
       t.cafeetemplatename as template,
       e.name              as module
from cafee f
join cafeetemplatefee tf on tf.cafeeid=f.cafeeid
join cafeetemplate t on t.cafeetemplateid=tf.cafeetemplateid
join caentity      e on e.caentityid=t.caentityid
where f.active=1
order by f.name;
