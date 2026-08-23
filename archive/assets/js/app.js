if (window.Chart) {
  Chart.defaults.font.family = 'Inter, Segoe UI, Arial, sans-serif';
  Chart.defaults.color = '#72809a';
  const palette=['#1667ff','#20b26b','#7b74f7','#ff9b21','#3fbfbd','#ef4444','#94a3b8'];
  const parse=(el,key,fallback=[])=>{try{return JSON.parse(el.dataset[key]||JSON.stringify(fallback));}catch(e){return fallback;}};

  const asset=document.getElementById('assetCategoryChart');
  if(asset)new Chart(asset,{type:'doughnut',data:{labels:parse(asset,'labels'),datasets:[{data:parse(asset,'values'),backgroundColor:palette,borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'64%',plugins:{legend:{position:'bottom'}}}});

  const wo=document.getElementById('workOrderStatusChart');
  if(wo)new Chart(wo,{type:'doughnut',data:{labels:['Selesai','Dalam Proses','Menunggu Spare Part','Open'],datasets:[{data:parse(wo,'values'),backgroundColor:['#20b26b','#1667ff','#ff9b21','#ef4444'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'64%',plugins:{legend:{position:'bottom'}}}});

  const service=document.getElementById('serviceStatusChart');
  const serviceStatuses=['Terlambat','Jatuh Tempo','Akan Service','Terjadwal','Selesai'];
  let serviceChart=null;
  if(service){serviceChart=new Chart(service,{type:'doughnut',data:{labels:serviceStatuses,datasets:[{data:parse(service,'values'),backgroundColor:['#ef4444','#ff9b21','#1667ff','#7b74f7','#20b26b'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'64%',plugins:{legend:{position:'bottom'}}}});}

  const serviceCategoryFilter=document.getElementById('serviceCategoryFilter');
  const serviceTimeFilter=document.getElementById('serviceTimeFilter');
  const serviceTable=document.getElementById('serviceScheduleTable');
  const serviceRows=serviceTable ? [...serviceTable.querySelectorAll('tbody tr[data-service-date]')] : [];
  const serviceInRange=(dateValue,range)=>{
    const date=new Date(`${dateValue}T00:00:00`), now=new Date();
    if(Number.isNaN(date.getTime())) return false;
    const today=new Date(now.getFullYear(),now.getMonth(),now.getDate());
    if(range==='TODAY') return date.getTime()===today.getTime();
    if(range==='WEEK'){const day=today.getDay()||7; const monday=new Date(today); monday.setDate(today.getDate()-day+1); const sunday=new Date(monday); sunday.setDate(monday.getDate()+6); return date>=monday&&date<=sunday;}
    if(range==='MONTH') return date.getFullYear()===today.getFullYear()&&date.getMonth()===today.getMonth();
    if(range==='YTD') return date.getFullYear()===today.getFullYear()&&date<=today;
    if(range==='YEAR') return date.getFullYear()===today.getFullYear();
    return true;
  };
  const applyServiceFilters=()=>{
    const category=serviceCategoryFilter?.value||'ALL', range=serviceTimeFilter?.value||'ALL', counts=Object.fromEntries(serviceStatuses.map(status=>[status,0]));
    let visible=0;
    serviceRows.forEach(row=>{const matches=(category==='ALL'||row.dataset.serviceCategory===category)&&serviceInRange(row.dataset.serviceDate,range); row.hidden=!matches; if(matches){visible++; counts[row.dataset.serviceStatus]=(counts[row.dataset.serviceStatus]||0)+1;}});
    if(serviceChart){serviceChart.data.datasets[0].data=serviceStatuses.map(status=>counts[status]||0); serviceChart.update();}
    const countLabel=document.getElementById('serviceFilterCount'); if(countLabel) countLabel.textContent=`${visible} unit ditampilkan`;
  };
  serviceCategoryFilter?.addEventListener('change',applyServiceFilters);
  serviceTimeFilter?.addEventListener('change',applyServiceFilters);

  const mech=document.getElementById('mechanicHoursChart');
  if(mech)new Chart(mech,{type:'bar',data:{labels:parse(mech,'labels'),datasets:[{label:'Jam Aktual',data:parse(mech,'actual'),backgroundColor:'#1667ff',borderRadius:5},{label:'Target',data:parse(mech,'targets'),backgroundColor:'#dbe6ff',borderRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'#edf1f7'}}}}});

  const cost=document.getElementById('costTrendChart');
  if(cost)new Chart(cost,{type:'bar',data:{labels:parse(cost,'labels'),datasets:[{label:'Biaya Perbaikan',data:parse(cost,'values'),backgroundColor:'#1667ff',borderRadius:5,maxBarThickness:30}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'#edf1f7'},ticks:{callback:v=>'Rp '+new Intl.NumberFormat('id-ID',{notation:'compact'}).format(v)}}}}});

  // Kompatibilitas halaman dashboard lama.
  const condition = document.getElementById('conditionChart');
  if (condition) {
    const values = parse(condition,'values',[0,0,0]);
    new Chart(condition,{type:'doughnut',data:{labels:['Breakdown','Ready for Use','Downtime'],datasets:[{data:values,backgroundColor:['#ef4444','#ffc000','#70ad47'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'66%',plugins:{legend:{position:'bottom'}}}});
  }
  const daily = document.getElementById('dailyConditionChart');
  if (daily && window.dashboardDailyUrl) {
    fetch(window.dashboardDailyUrl).then(r=>r.json()).then(rows=>new Chart(daily,{type:'bar',data:{labels:rows.map(r=>r.event_date),datasets:[{label:'Breakdown',data:rows.map(r=>r.breakdown),backgroundColor:'#ef4444'},{label:'Ready for Use',data:rows.map(r=>r.ready),backgroundColor:'#ffc000'},{label:'Downtime',data:rows.map(r=>r.downtime),backgroundColor:'#70ad47'}]},options:{responsive:true,maintainAspectRatio:false,scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}}}})).catch(()=>{});
  }
}
