import React from 'react'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

const WIPTable = () => {
  return (
      <AuthenticatedLayout>
          <Head title="WIP Table" />
          <h1 className="text-2xl font-bold">WIP Table</h1>
      </AuthenticatedLayout>
  );
}

export default WIPTable