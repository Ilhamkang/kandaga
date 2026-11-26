(function () {
    'use strict';
    var root = document.querySelector('[data-kandaga-base]');
    if (!root) return;
    var base = root.dataset.kandagaBase, key = 'kandaga.bookmarks.v1:' + base, limit = 2000;
    var page = 1, pageSize = 15, request = 0;
    var list = document.getElementById('bookmarkResults'), summary = document.getElementById('bookmarkSummary');
    function announce(message, error) {
        var status = document.getElementById('bookmarkStatus');
        if (status) {status.textContent=message; status.className='container mt-2 '+(error?'alert alert-warning':'small text-muted');}
    }
    function read() {
        var raw=localStorage.getItem(key), values=raw?JSON.parse(raw):[];
        if (!Array.isArray(values)) throw new Error('Arsip bookmark browser tidak valid.');
        var seen=new Set();
        return values.filter(function(item){if(!item||!Number.isSafeInteger(item.id)||item.id<1||item.id>2147483647||seen.has(item.id))return false;seen.add(item.id);return true;}).slice(0,limit);
    }
    function updateButtons(items) {
        var ids=new Set(items.map(function(item){return item.id;}));
        document.querySelectorAll('[data-kandaga-bookmark]').forEach(function(button){
            var saved=ids.has(Number(button.dataset.kandagaBookmark));
            button.setAttribute('aria-pressed',saved?'true':'false');
            button.classList.toggle('btn-primary',saved);button.classList.toggle('btn-outline-primary',!saved);
            var icon=button.querySelector('i'),text=button.querySelector('span');
            if(icon)icon.className=(saved?'fa-solid':'fa-regular')+' fa-bookmark mr-1';
            if(text)text.textContent=saved?'Tersimpan':'Bookmark';
            button.setAttribute('aria-label',(saved?'Hapus bookmark: ':'Bookmark: ')+(button.dataset.bookmarkTitle||'Naskah '+button.dataset.kandagaBookmark));
        });
        document.querySelectorAll('[data-bookmark-count]').forEach(function(count){count.textContent=items.length;});
    }
    function element(tag,cls,text){var e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=text;return e;}
    function card(id,item) {
        var article=element('article','card search-list-card shadow-sm mb-3'),layout=element('div','search-list-layout');
        var body=element('div','search-list-body'),title=element('h2','h6 card-title search-list-title');
        var name=item?item.title:'Naskah #'+id+' sudah tidak tersedia';
        if(item){
            var img=element('img','search-list-image');img.src=item.image;img.alt='';img.loading='lazy';
            img.addEventListener('error',function(){this.onerror=null;this.src=base+'/assets/images/msc-placeholder.png';},{once:true});
            layout.appendChild(img);
            var link=element('a','',name);link.href=base+'/detail.php?id='+id;title.appendChild(link);
        } else title.textContent=name;
        body.appendChild(title);
        if(item){body.appendChild(element('p','small text-muted mb-1',item.location));body.appendChild(element('p','small text-primary mb-0',item.inventory));}
        layout.appendChild(body);
        var button=element('button','btn btn-sm btn-primary kandaga-bookmark-button');button.type='button';button.dataset.kandagaBookmark=id;button.dataset.bookmarkTitle=name;button.setAttribute('aria-pressed','true');
        var icon=element('i','fa-solid fa-bookmark mr-1');icon.setAttribute('aria-hidden','true');button.appendChild(icon);button.appendChild(element('span','','Tersimpan'));layout.appendChild(button);article.appendChild(layout);return article;
    }
    async function render() {
        if(!list)return;
        var token=++request,items;
        try {items=read();} catch(error){list.textContent='Bookmark tidak dapat dibaca. Izinkan penyimpanan lokal browser untuk menggunakan fitur ini.';announce(error.message,true);return;}
        var totalPages=Math.max(1,Math.ceil(items.length/pageSize));page=Math.min(page,totalPages);
        summary.textContent=items.length+' bookmark — halaman '+page+' dari '+totalPages;
        var nav=document.getElementById('bookmarkPagination');nav.replaceChildren();
        if(totalPages>1){[['Sebelumnya',-1],['Berikutnya',1]].forEach(function(pair){var b=element('button','btn btn-sm btn-outline-primary mx-1',pair[0]);b.type='button';b.disabled=pair[1]<0?page===1:page===totalPages;b.onclick=function(){page+=pair[1];render();};nav.appendChild(b);});}
        if(!items.length){list.replaceChildren(element('p','alert alert-info','Belum ada bookmark. Tandai naskah melalui tombol Bookmark pada pencarian atau detail naskah.'));return;}
        list.textContent='Memuat bookmark…';
        var slice=items.slice((page-1)*pageSize,page*pageSize),ids=slice.map(function(item){return item.id;});
        try {
            var response=await fetch(base+'/modules/bookmark-data.php?ids='+ids.join(','),{credentials:'same-origin',cache:'no-store'});
            if(!response.ok)throw new Error('Data bookmark belum dapat dimuat.');
            var data=await response.json();if(!Array.isArray(data.items))throw new Error('Respons bookmark tidak valid.');
            if(token!==request)return;
            var byId=new Map(data.items.map(function(item){return [item.id,item];}));list.replaceChildren();
            ids.forEach(function(id){list.appendChild(card(id,byId.get(id)));});updateButtons(items);
        } catch(error){
            if(token!==request)return;
            list.replaceChildren(element('p','alert alert-warning',error.message+' Bookmark tetap tersimpan.'));
            var retry=element('button','btn btn-outline-primary','Coba lagi');retry.type='button';retry.onclick=render;list.appendChild(retry);
        }
    }
    document.addEventListener('click',function(event){
        var button=event.target.closest('[data-kandaga-bookmark]');if(!button)return;
        event.preventDefault();event.stopPropagation();
        try {
            var id=Number(button.dataset.kandagaBookmark);if(!Number.isSafeInteger(id)||id<1||id>2147483647)return;
            var items=read(),exists=items.some(function(item){return item.id===id;});
            if(!exists&&items.length>=limit)throw new Error('Maksimal '+limit+' bookmark. Hapus beberapa bookmark sebelum menambah lagi.');
            items=exists?items.filter(function(item){return item.id!==id;}):[{id:id,added:new Date().toISOString()}].concat(items);
            localStorage.setItem(key,JSON.stringify(items));updateButtons(items);announce(exists?'Bookmark dihapus.':'Naskah ditambahkan ke bookmark.');render();
        } catch(error){announce('Bookmark gagal disimpan. '+error.message,true);}
    },true);
    window.addEventListener('storage',function(event){if(event.key===key||event.key===null){try{updateButtons(read());render();}catch(error){announce('Bookmark tidak dapat dibaca.',true);}}});
    try {updateButtons(read());render();} catch(error){announce('Bookmark tidak dapat dibaca. Izinkan penyimpanan lokal browser.',true);}
})();
