<template>
    <h3>{{licence.computedName}}</h3>
    <p v-if="licence.description">
        {{licence.description}}
    </p>

    <ul class="list-group list-group-flush">
        <li class="list-group-item" v-for="(value, name) in licence" v-show="name != 'description'" :key="'licence-data-'+name">
            <strong class="me-2 text-secondary">{{name}}</strong>
            <template v-if="typeof value === 'object'">
                <div v-for="(val, index) in value" :key="'licence-data-'+name+'-'+index" class="d-flex align-items-center ps-4">
                    <span class="badge bg-secondary me-2">{{index}}</span>
                    <span>{{val}}</span>
                </div>
            </template>
            <template v-else>
                {{value}}
            </template>
        </li>
    </ul>
</template>

<script>

export default {
    props: {
        licence: Object
    }
}

</script>