<template>
    <form>
        <div class="form-group" v-for="field in fields">
            <label>{{field.label}}</label>
            <input  v-if="field.type==='text'" type="text" class="form-control" :placeholder="field.label" v-model="values[field.value]" v-on:change="change">
            <input  v-if="field.type==='password'" type="password" class="form-control" :placeholder="field.label" v-model="values[field.value]" v-on:change="change">
            <input  v-if="field.type==='email'" type="email" class="form-control" :placeholder="field.label" v-model="values[field.value]" v-on:change="change">
            <div v-if="field.type==='file' || field.type==='multiplefile'" class="input-group">
                <span v-if="field.type!=='multiplefile'" class="input-group-btn">
                    <a class="btn btn-default" title="Descargar" :href="file(values[field.value]).url" :download="file(values[field.value]).name"><i class="fa fa-download"></i></a>
                </span>
                <input type="text" readonly="readonly" v-model="file(values[field.value], field.type==='multiplefile').name" class="form-control form-file-progress">
                <span class="input-group-btn">
                    <span class="btn btn-default" type="button" style="cursor: pointer;" title="Cargar"><i class="fa fa-folder-open"></i><input type="file" style="
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        left: 0px;
                        top: 0px;
                        opacity: 0;"
                        v-on:change="changeFile($event, field, field.type==='multiplefile')"
                        v-bind:multiple="field.type==='multiplefile'">
                    </span>
                </span>
            </div>
            <select v-if="field.type==='select'" class="form-control" :placeholder="field.label" :value="getInitialValueOf(values[field.value])" v-on:change="changeSelect($event, field)">
                <option value=""></option>
                <option v-for="option in domains[field.name]" v-bind:value="option.id">{{getTextField(option.attributes, field.textField)}}</option>
                <option v-bind:value="getInitialValueOf(values[field.value])" hidden="">{{getInitialTextOf(values[field.value], field.textField)}}</option>
            </select>
            <tags v-if="field.type==='tags'" :placeholder="field.label" :model="values" :property="field.value" :domain="domains[field.name]" :field="field" v-on:change="change" />
            <htmleditor v-if="field.type==='html'" :model="values" :property="field.value" v-on:change="change" />
            <filters v-if="field.type==='filter'" :placeholder="field.label" :model="values" :property="field.value" :domain="domains[field.name]" :field="field"  v-on:change="change"/>
        </div>
        <button v-for="button in myButtons" v-if="button=='reset'" type="button" v-on:click="reset" class="btn btn-default">Reestablecer</button>
        <button v-else-if="button=='cancel'" type="button" v-on:click="cancel" class="btn btn-warning">Cancelar</button>
        <button v-else-if="button=='close'" type="button" v-on:click="cancel" class="btn btn-warning">Cerrar</button>
        <button v-else-if="button=='save'" type="button" v-on:click="save" class="btn btn-success">Guardar</button>
        <button v-else-if="button=='update'" type="button" v-on:click="update" class="btn btn-primary">Aplicar</button>
        <button v-else-if="button=='delete'" type="button" v-on:click="remove" class="btn btn-danger">Eliminar</button>
        <button v-else type="button" v-on:click="custom(button)" class="btn btn-default">{{button}}</button>
    </form>
</template>

<script>
    export default {
        props:[
            "id",
            "model",
            "childrenurl",
            "buttons",
        ],
        data() {
            var self = this;
            var fields = this.model.$fields();
            var domains = {};
            fields.forEach(function(f){
                domains[f.name] = self.model.$domain(f, function(domain) {});
            });
            return {
                fields: fields,
                values: this.model,
                domains: domains,
            };
        },
        computed: {
            myButtons: function() {
                var self = this;
                var buttons = !self.buttons?'close,save':self.buttons;
                return buttons.split(',');
            }
        },
        methods: {
            reset: function() {
                this.model.$reset();
                this.$emit('reset');
            },
            cancel: function() {
                this.$emit('cancel');
            },
            save: function() {
                var self = this;
                this.model.$save(this.childrenurl, function(){
                    self.$emit('save');
                });
            },
            update: function() {
                var self = this;
                this.model.$save(this.childrenurl, function(){
                    self.$emit('update');
                });
            },
            remove: function() {
                var self = this;
                this.model.$delete(this.childrenurl, function(){
                    self.$emit('delete');
                });
            },
            custom: function(button) {
                var self = this;
                self.$emit('custom', button, self.model, self);
            },
            change: function(event) {
                var self = this;
                self.$root.$emit('changed', self);
            },
            changeSelect: function(event, field) {
                var value = event.target.value;
                var self = this;
                self.values[field.value] = value;
                self.$root.$emit('changed', self);
            },
            changeFile: function(event, field, multiple) {
                var self = this;
                var data = new FormData();
                for(var i=0,l=event.target.files.length;i<l;i++) {
                    data.append('file'+(multiple?'[]':''), event.target.files[i]);
                }
                $.ajax({
                    url: API_SERVER+"/api/uploadfile",
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (json) {
                        self.values[field.value] = json;
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                }).uploadProgress(function(data) {
                    if (data.lengthComputable) {
                        var progress = parseInt( ((data.loaded / data.total) * 100), 10 );
                        $(event.target)
                            .closest(".input-group")
                            .find(".form-file-progress")
                            .css("cssText", "background-size: "+progress+"% 100%!important");
                    }
                });
            },
            file:function(json, multiple){
                var name='', url=false, mime='';
                url = json && typeof json.url!=='undefined' ? json.url : false;
                mime = json && typeof json.mime!=='undefined' ? json.mime : '';
                if (multiple && json && typeof json.forEach==='function') {
                    json.forEach(function (j) {
                        name+=j.name+' | ';
                    });
                } else {
                    name = json && typeof json.name!=='undefined' ? json.name : '';
                }
                return {name:name, url:url, mime: mime};
            },
            getInitialValueOf: function (value) {
                var val = typeof value==='object' && value && typeof value.id!=='undefined' ? value.id : value;
                return val;
            },
            getInitialTextOf: function (value, textField, field) {
                var val = typeof value==='object' && value && typeof value.attributes!=='undefined' ? (typeof textField === 'function' ? textField(value.attributes) : value.attributes[textField]) : value;
                return val;
            },
            getTextField: function (data, textField) {
                return typeof textField==='function' ? textField(data) : data[textField];
            },
        },
        mounted() {
            var vm = this;
            this.model.setCallback(function(){
                var fields = vm.fields;
                var ff = vm.model.$fields();
                fields.length = 0;
                for(var a in ff) {
                    if(typeof ff[a]!=='function') {
                        fields.push(ff[a]);
                    }
                }
                fields.forEach(function(f){
                    vm.domains[f.name].refresh(function() {
                    });
                });
                vm.$forceUpdate();
                vm.$children.forEach(function(ch){
                    if(typeof ch.refresh==='function'){
                        ch.refresh();
                    }
                });
            });
        }
    }
</script>
