<template>
    <carousel :id="id">
        <carouselitem class="active">
            <datatable :model="model" v-on:newrecord="newRecord" v-on:selectrow="selectRow" :toolbar="toolbar" :editable="editable"></datatable>
        </carouselitem>

        <carouselitem>
            <dv-form :id="'form@'+id" :model="model" v-on:cancel="cancelEdit" v-on:save="saveRow" v-on:update="updateRow" v-on:delete="deleteRow" v-on:custom="custom" :buttons="buttons"></dv-form>
        </carouselitem>
    </carousel>
</template>

<script>
    export default {
        props:[
            "id",
            "model",
            "filter",
            "nameField",
            "refreshWith",
            "buttons",
            "toolbar",
            "editable",
        ],
        carousel:{},
        data: function(){
            return {
                path: [[/*new PathItem({name: this.model.$pluralName, item: 0}, this)*/],[]],
            };
        },
        methods: {
            goto: function(viewId) {
                if(viewId===1) {
                    this.path[1]=new PathItem({name: '* '+(this.model[this.nameField]?this.model[this.nameField]:''), item: 1}, this);
                } else {
                    this.path[1]=[];
                }
                app.pathChanged++;
                this.carousel.slider(viewId);
            },
            newRecord: function(){
                var self=this;
                this.model.$load(0, function() {
                    self.goto(1);
                    self.$emit('new', self.model, self);
                });
            },
            selectRow: function(id, data){
                var self = this;
                this.model.$load(id, function(){
                    self.$root.$emit('changed', self);
                    self.$emit('selectrow', self.model, self);
                    if (typeof self.editable==='undefined' || self.editable) {
                        self.goto(1);
                        self.$emit('edit', self.model, self);
                    }
                });
            },
            cancelEdit: function(){
                this.goto(0);
                this.$emit('cancel', this.model, this);
            },
            saveRow: function(){
                this.model.$refreshList();
                this.goto(0);
                this.$emit('save', this.model, this);
                this.$root.$emit('changed', this);
            },
            updateRow: function(){
                this.model.$refreshList();
                this.$emit('update', this.model, this);
                this.$root.$emit('changed', this);
            },
            deleteRow: function(){
                this.model.$refreshList();
                this.goto(0);
                this.$emit('delete', this.model, this);
                this.$root.$emit('changed', this);
            },
            custom: function(button, model, form) {
                this.$emit(button, model, form);
            },
        },
        mounted: function() {
            var self = this;
            this.carousel = this.$children[0];
            this.datatable = this.carousel.$children[0].$children[0];
            this.$root.$on('changed', function(element){
                if(typeof element.id!=='undefined') {
                    if(element.id===self.refreshWith) {
                        self.datatable.refresh();
                    }
                }
            });
        }
    }
</script>
