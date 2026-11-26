(function () {
    'use strict';
    var data = document.getElementById('contributorActivityData');
    var canvas = document.getElementById('contributorMonthlyChart');
    var activity = data ? JSON.parse(data.textContent) : null;
    if (activity && canvas && window.Chart) {
        var people = activity.contributors;
        var colors = ['#2563eb','#059669','#d97706','#7c3aed','#dc2626','#0891b2','#475569'];
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        var chart = new Chart(canvas, {type:'bar',data:{labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],datasets:people.map(function (p, i) {return {label:p.name,data:p.input.map(function (v,m) {return v+p.edit[m];}),inputCounts:p.input,editCounts:p.edit,backgroundColor:colors[i%colors.length]};})},options:{responsive:true,maintainAspectRatio:false,scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true,ticks:{precision:0}}},plugins:{tooltip:{callbacks:{label:function (ctx) {var d=ctx.dataset,m=ctx.dataIndex;return d.label+': '+d.inputCounts[m]+' input, '+d.editCounts[m]+' pembaruan';}}}}}});
    }
    var monthNames=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    function applyActivity(next) {
        if (!next || !Array.isArray(next.contributors) || !next.days || next.year !== activity.year) return;
        var total=document.getElementById('activityTotal');if(total)total.textContent=Number(next.total).toLocaleString('id-ID');
        document.querySelectorAll('[data-activity-day]').forEach(function(cell){
            var day=cell.dataset.activityDay,entry=next.days[day]||{total:0,people:{}},n=entry.total;
            var parts=day.split('-'),text=parts[2]+'-'+parts[1]+'-'+parts[0]+': '+n+' aktivitas';
            Object.values(entry.people).forEach(function(p){text+='\n'+p.name+': '+p.input+' input, '+p.edit+' pembaruan';});
            cell.className='activity-day activity-level-'+(n===0?0:n<=2?1:n<=5?2:n<=10?3:4);
            cell.dataset.activityTooltip=text;cell.title=text;cell.setAttribute('aria-label',text);
        });
        if(chart){chart.data.datasets=next.contributors.map(function(p,i){return {label:p.name,data:p.input.map(function(v,m){return v+p.edit[m];}),inputCounts:p.input,editCounts:p.edit,backgroundColor:colors[i%colors.length]};});chart.update();}
        var rows=document.getElementById('activityMonthlyRows');
        if(rows){rows.replaceChildren();next.contributors.forEach(function(p){monthNames.forEach(function(m,i){if(!p.input[i]&&!p.edit[i])return;var tr=document.createElement('tr');[p.name,m,p.input[i],p.edit[i]].forEach(function(v){var td=document.createElement('td');td.textContent=v;tr.appendChild(td);});rows.appendChild(tr);});});
            if(!next.total){var tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=4;td.textContent='Belum ada aktivitas tercatat pada tahun ini.';tr.appendChild(td);rows.appendChild(tr);}
        }
    }
    var refreshing=false;
    async function refreshActivity(){
        if(refreshing||document.hidden||!activity||!data.isConnected)return;
        refreshing=true;
        try{var nav=document.querySelector('[data-kandaga-base]');var base=nav?nav.dataset.kandagaBase:data.dataset.base;var r=await fetch(base+'/modules/contribution-data.php?year='+activity.year,{credentials:'same-origin',cache:'no-store'});if(r.ok)applyActivity(await r.json());}catch(error){}finally{refreshing=false;}
    }
    var activityTimer=window.setInterval(function(){if(!data.isConnected){clearInterval(activityTimer);document.removeEventListener('visibilitychange',refreshActivity);if(chart)chart.destroy();if(tip)tip.remove();return;}refreshActivity();},60000);
    document.addEventListener('visibilitychange',refreshActivity);
    var tip = document.createElement('div'); tip.className = 'activity-tooltip'; tip.hidden = true; tip.setAttribute('role','tooltip'); tip.id = 'activityTooltip'; document.body.appendChild(tip);
    function show(cell) {
        tip.textContent = cell.dataset.activityTooltip; tip.hidden = false; cell.setAttribute('aria-describedby',tip.id);
        var r=cell.getBoundingClientRect();
        tip.style.left=Math.max(8,Math.min(r.left,window.innerWidth-tip.offsetWidth-8))+'px';
        tip.style.top=(r.bottom+tip.offsetHeight+12>window.innerHeight?Math.max(8,r.top-tip.offsetHeight-8):r.bottom+8)+'px';
    }
    document.querySelectorAll('[data-activity-tooltip]').forEach(function (cell) {
        cell.addEventListener('mouseenter',function(){show(cell);});
        cell.addEventListener('focus',function(){show(cell);});
        ['mouseleave','blur'].forEach(function (event) {cell.addEventListener(event,function(){tip.hidden=true;cell.removeAttribute('aria-describedby');});});
        cell.addEventListener('keydown',function(event){if(event.key==='Escape')tip.hidden=true;});
    });
})();
