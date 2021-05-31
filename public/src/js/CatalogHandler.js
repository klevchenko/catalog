function CatalogHandler(){
    
    let formattedCSVData = [];

    let form = $('.ajax_add_catalog');
    let fileInput = form.find("input[type='file']");
    let submitInput = form.find("button[type='submit']");
    let stopDownloadingBtn = form.find(".btn-stop-downloading");
    let ajaxURL = form.find("input[name='ajax_url']").val();

    let progressBarWrap = $(".progress-bar-wrap");
    let progressBar = progressBarWrap.find(".progress-bar");

    let ajaxToken = form.find("input[name='token']").val();

    let parsedCSVPreview = $(".parsed_csv_list");
    let errorsWrap = $(".errors_wrap");

    let fileName = '';
    let request = undefined;

    let $this = this;
    let valid1lvl = false;
    let valid2lvl = false;


    this.resetForm = function(resetFile = false){

        parsedCSVPreview.html('');
        parsedCSVPreview.addClass('d-none');

        if(resetFile){
            fileInput.val('');
        }
        submitInput.text(submitInput.data('def-val'));
        form.find('.alert-downloading-stopped').addClass('d-none');
        progressBarWrap.addClass('d-none');
    }

    this.init = function(){

        $this.resetForm();

            form.submit( (e) => {
                e.preventDefault();

                if(valid1lvl && valid2lvl && formattedCSVData.length){
                    $this.persistParsedData();
                    return false;
                }

                fileInput.parse({
                    config: {
                        delimiter: "auto",
                        complete: this.formatParsedData,
                    },
                    before: (file, inputElem) => {
                        console.log("Parsing file...", file);
                        fileName = file.name;
                        submitInput.text(submitInput.data('loading'));
                    },
                    error: (err, file) => {
                        console.log("ERROR:", err, file);
                        this.showError();
                        this.resetForm();
                    },
                    complete: () => {
                        console.log("Done with all files");
                        setTimeout( () => {
                            submitInput.text(submitInput.data('complete'));
                        }, 500)
                    }
                });

            })
    }

    this.formatParsedData = function(results){

        let data = results.data;

        formattedCSVData = [];

        try {
            for (let i = 0; i < data.length; i++) {

                let row = data[i];
                let cells = row.join(",").split(",");

                if (cells && cells[0] && cells[1] && cells[2]) {
                    formattedCSVData.push([cells[0], cells[1], cells[2]]);
                }
            }

            $this.displayHTMLTablePreview(formattedCSVData);

            valid1lvl = true;

        } catch (e) {
            valid1lvl = false;
            console.log('//////////////////////////////////////////');
            console.log(e);
            console.log('//////////////////////////////////////////');
        } finally {
            if (valid1lvl === false) {
                $this.showError();
                $this.resetForm(true);
            }
        }
    }

    this.displayHTMLTablePreview = function (data) {

        try {
            let table = '';
            table += '';
            table += '<p class="m-3 alert alert-warning">Перевірте частину файлу '+fileName+' перед завантаженням.</p>';
            table += "<table class='table table-bordered m-3' style=\"max-width: 95%;\">";

            for (i = 0; i < 100; i++) {
                table += "<tr>";
                let row = data[i];
                let cells = row;

                for (j = 0; j < 3; j++) {
                    table += "<td>";
                    table += cells[j];
                    table += "</th>";
                }
                table += "</tr>";
            }
            table += "</table>";

            parsedCSVPreview.html(table);

            valid2lvl = true;

        } catch (e) {
            valid2lvl = false;
            console.log('//////////////////////////////////////////');
            console.log(e);
            console.log('//////////////////////////////////////////');
        } finally {

            if(valid2lvl){
                parsedCSVPreview.removeClass('d-none');
            } else {
                $this.showError();
                $this.resetForm(true);            
            }
        }
    }

    this.showError = function () {
        errorsWrap.html('<div class="m-3 alert alert-danger" role="alert">Помилка при завантаженні файлу каталогу. Перевірте файл і спробуйте знову.</div>');
        errorsWrap.removeClass('d-none');

        setTimeout(function () {
            errorsWrap.html('');
            errorsWrap.addClass('d-none');
        }, 4000)
    }


    this.persistParsedData = function () {
       
        $this.resetForm();  
        submitInput.fadeOut(0);
        progressBarWrap.removeClass('d-none');

        request = $.ajax({
            url: ajaxURL,
            type: "post",
            data: {
                fileName: fileName,
                token : ajaxToken,
            }
        });

        request.done(function (response, textStatus, jqXHR){                    
            if(textStatus === 'success' && response.status === true && response.data && response.data.catalog_id ){

                let qInterval           = undefined;
                let queue               = [];
                let queueCount          = 0;
                let queueMaxCount       = formattedCSVData.length;
                let queueRequest        = undefined;
                let pendingRequests     = [];
                let catalog_id          = response.data.catalog_id;
                let maxStreamCount      = 10;
                let streamCount         = 0;

                let progressBarPersent  = '';
                

                stopDownloadingBtn.click(() => {
                    if(confirm("Bи впевнені що хочете зупинити завантаження каталога?")){
                        clearInterval(qInterval);
                        progressBarWrap.addClass('d-none');
                        stopDownloadingBtn.addClass('d-none');
                        form.find('.alert-downloading-stopped').removeClass('d-none');
                    }
                });

                qInterval = setInterval(() => {

                    progressBarPersent = queueCount / queueMaxCount * 100;
                    progressBar.text(parseInt(progressBarPersent) + '%');

                    progressBarPersent = progressBarPersent + '%';
                    progressBar.width(progressBarPersent);
            
                    if(queue.length > 0 && streamCount < maxStreamCount){
                       
                        streamCount++;
                        pendingRequests.push('1');
                        queueRequest = $.ajax({
                            url: ajaxURL,
                            type: "post",
                            data: {
                                json: JSON.stringify(queue.splice(0, 1)[0]),
                                token : ajaxToken,
                                catalog_id : catalog_id,
                            }
                        });
    
                        queueRequest.always( () => {
                            //queue.splice(0, 1);
                            queueCount++;
                            streamCount--;
                            pendingRequests = pendingRequests.splice(0, 1);
                        });
                    }
                    

                    if( formattedCSVData.length < 1 ){
                        progressBarWrap.addClass('d-none');
                        stopDownloadingBtn.addClass('d-none');

                        $this.resetForm(true);
                        form.find('.alert-downloading-success').removeClass('d-none');

                        clearInterval(qInterval);

                        setTimeout(() => {window.location.reload()}, 1000);
                    }

                    if(queue.length === 0){
                        queue.push(formattedCSVData.splice(0, 1)[0])
                    }

                }, 50);

            
                
            }
        });

        request.fail(function (jqXHR, textStatus, errorThrown){
            console.error(
                "The following error occurred: "+
                textStatus, errorThrown
            );
        });

        request.always(function () {
            
        });
        
    }
}

setTimeout(function() {

    let catalogHandler = new CatalogHandler();
    catalogHandler.init();

}, 400);

