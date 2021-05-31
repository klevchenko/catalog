let catalogHandler = (function(){
    let formattedCSVData = [],
        form = $('.ajax_add_catalog'),
        fileInput = form.find("input[type='file']"),
        nameInput = form.find("input[type='name']"),
        submitInput = form.find("button[type='submit']"),

        parsedCSVPreview = $(".parsed_csv_list"),
        errorsWrap = $(".errors_wrap");

    return {
        init : function () {
            this._init();
        },
        resetForm : function (){
            parsedCSVPreview.html('');
            parsedCSVPreview.addClass('d-none');

            errorsWrap.html('');
            errorsWrap.addClass('d-none');

            fileInput.val();
            nameInput.val();
        },

        _init : function (){
            catalogHandler.resetForm();
            console.log("init");

            form.submit(function (e) {
                e.preventDefault();

                catalogHandler.resetForm();

                fileInput.parse({
                    config: {
                        delimiter: "auto",
                        complete: catalogHandler.formatParsedData,
                    },
                    before: function (file, inputElem) {
                        console.log("Parsing file...", file);
                        submitInput.text('Перевірка файлу каталогу');
                    },
                    error: function (err, file) {
                        console.log("ERROR:", err, file);
                        catalogHandler.showError();
                        catalogHandler.resetForm();
                    },
                    complete: function () {
                        console.log("Done with all files");
                        parsedCSVPreview.removeClass('d-none');
                        setTimeout(function () {
                            submitInput.text('Завантажити каталог');
                        }, 500)
                    }
                });

            })
        },

        sanitizeString : function (str){
            str = str.replace(/[^a-z0-9áéíóúñü \.,_-]/gim,"");
            return str.trim();
        },

        formatParsedData : function (results) {

            let data = results.data;
            catalogHandler.formattedCSVData = [];

            try {
                for (let i = 0; i < data.length; i++) {

                    let row = data[i];
                    let cells = row.join(",").split(",");

                    if (cells && cells[0] && cells[1] && cells[2]) {
                        catalogHandler.formattedCSVData.push([cells[0], cells[1], cells[2]]);
                    }
                }

                catalogHandler.displayHTMLTablePreview(catalogHandler.formattedCSVData);

                console.log('-----------------------------------');
                console.log(catalogHandler.formattedCSVData.length);
                if (catalogHandler.formattedCSVData.length < 500) {
                    console.log(catalogHandler.formattedCSVData)
                }
                console.log('-----------------------------------');

            } catch (e) {
                catalogHandler.showError();
            } finally {
                if (catalogHandler.formattedCSVData.length < 1) {
                    catalogHandler.showError();
                }
            }
        },

        showError : function () {
            catalogHandler.errorsWrap.html('<div class="m-3 alert alert-danger" role="alert">Помилка при завантаженні файлу каталогу. Перевірте файл і спробуйте знову.</div>');

            catalogHandler.parsedCSVPreview.html('');
            catalogHandler.parsedCSVPreview.addClass('d-none');

            setTimeout(function () {
                catalogHandler.errorsWrap.html('');
                catalogHandler.errorsWrap.addClass('d-none');
            }, 4000)
        },

        persistParsedData : function (results) {

        },

        displayHTMLTablePreview : function (data) {
            try {
                let table = '';
                table += '';
                table += '<p class="m-3 alert alert-warning">Перевірте перші 100 рядків файлу перед завантаженням.</p>';
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

                $(".parsed_csv_list").html(table);
            } catch (e) {
                catalogHandler.showError();
            } finally {

            }
        }
    }
})();







