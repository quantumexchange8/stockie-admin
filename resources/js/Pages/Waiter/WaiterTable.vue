<script setup>
import { PlusIcon } from "@/Components/Icons/solid";
</script>

<template>
    <table class="w-full table-auto">
        <thead>
            <tr>
                <th>
                    <div class="flex justify-between">
                        <div>ID</div>
                        <div><PlusIcon></PlusIcon></div>
                    </div>
                </th>
                <th>Waiter</th>
                <th>Phone</th>
                <th>Email</th>
            </tr>
        </thead>
    </table>
</template>
