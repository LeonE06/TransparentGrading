<template>

    <div class="header">
        <DarkLightMode />
        <svg class="profile" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="30" fill="var(--second-background-color)" />
            <path
                d="M30.0002 28C32.9917 28 35.4168 25.5748 35.4168 22.5833C35.4168 19.5917 32.9917 17.1666 30.0002 17.1666C27.0086 17.1666 24.5835 19.5917 24.5835 22.5833C24.5835 25.5748 27.0086 28 30.0002 28Z"
                stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path
                d="M39.306 38.8333C39.306 34.6408 35.1352 31.25 30.0002 31.25C24.8652 31.25 20.6943 34.6408 20.6943 38.8333"
                stroke="var(--icon-color)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

    </div>

</template>

<script setup>
import DarkLightMode from '@/components/DarkLightMode.vue'
</script>

<style>
.header {
    display: flex;
    justify-content: flex-end;
    padding-right: 2rem;
}

.profile {
    margin-left: 1rem;
}
</style>