import {Link, Head} from '@inertiajs/react';
import MainLayout from "@/Layouts/MainLayout.jsx";

export default function Welcome({auth}) {
    return (
        <MainLayout auth={auth}>
            <main>
                <div className="row">
                    <div className="col-3">
                        <div className="card mb-4 rounded-3 shadow-sm">
                            <div className="card-header py-3">
                                <h4 className="my-0 fw-normal">Welcome</h4>
                            </div>
                            <div className="card-body">

                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </MainLayout>
    );
}
