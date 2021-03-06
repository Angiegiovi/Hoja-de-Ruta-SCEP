<template>
    <div>
        <canvas></canvas>
        <div class="textLayer"></div>
    </div>
</template>

<script>
    PDFJS.workerSrc = '/js/pdf.worker.js';
    export default {
        props:[
            "url",
            "page",
            //"numPages",
        ],
        data() {
            return {
            };
        },
        methods: {
            /**
             * Get page info from document, resize canvas accordingly, and render page.
             * @param num Page number.
             */
            renderPage : function (num) {
                var self = this;
                num = Math.min(Math.max(num * 1, 1), self.numPages);
                if (!num) return;
                self.pageRendering = true;
                // Using promise to fetch the page
                self.pdfDoc.getPage(num).then(function (page) {
                    var viewport = page.getViewport(self.scale);
                    self.canvas.height = viewport.height;
                    self.canvas.width = viewport.width;

                    // Render PDF page into canvas context
                    var renderContext = {
                        canvasContext: self.ctx,
                        viewport: viewport
                    };
                    var canvasOffset = $(self.canvas).offset();
                    self.textLayer.css({
                        height : viewport.height+'px',
                        width : viewport.width+'px',
                        top : canvasOffset.top,
                        left : canvasOffset.left
                    });

                    var renderTask = page.render(renderContext);

                    // Wait for rendering to finish
                    renderTask.promise.then(function () {
                        self.pageRendering = false;
                        if (self.pageNumPending !== null) {
                            // New page rendering is pending
                            self.renderPage(self.pageNumPending);
                            self.pageNumPending = null;
                        }
                    });
                    page.getTextContent().then(function(textContent){
                        var textLayer = PDFJS.renderTextLayer({
                            textContent: textContent,
                            textLayerDiv : self.textLayer.get(0),
                            pageIndex : 1,
                            viewport : viewport
                        });
                        textLayer.render();
                    });
                });
            },
            /**
             * If another page rendering in progress, waits until the rendering is
             * finised. Otherwise, executes rendering immediately.
             */
            queueRenderPage : function (num) {
                var self = this;
                if (self.pageRendering) {
                    self.pageNumPending = num;
                } else {
                    self.renderPage(num);
                }
            },
            /**
             * Displays previous page.
             */
            previous : function () {
                var self = this;
                if (self.page <= 1) {
                    return;
                }
                self.page--;
                self.queueRenderPage(self.page);
            },
            /**
             * Displays next page.
             */
            next : function () {
                var self = this;
                if (self.page >= self.pdfDoc.numPages) {
                    return;
                }
                self.page++;
                self.queueRenderPage(self.page);
            },
            refresh : function () {
                var self = this;
                return;
                /**
                 * Asynchronously downloads PDF.
                 */
                PDFJS.getDocument(self.url).then(function (pdfDoc_) {
                    self.pdfDoc = pdfDoc_;
                    self.numPages = self.pdfDoc.numPages;
                    // Initial/first page rendering
                    self.renderPage(self.page);
                    self.$emit('load', self.numPages);
                });
            }
            
        },
        watch : {
            url : function () {
                var self = this;
                self.refresh();
            },
            page : function (page) {
                var self = this;
                self.queueRenderPage(page);
            }
        },
        mounted: function() {
            var self = this;
            self.pdfDoc = null;
            self.pageRendering = false;
            self.pageNumPending = null;
            self.scale = 0.8;
            self.canvas = $(this.$el).find('canvas')[0];
            self.textLayer = $(this.$el).find('.textLayer');
            self.ctx = self.canvas.getContext('2d');
            Vue.nextTick(function () {
                self.refresh();
            });
        }
    }
</script>
