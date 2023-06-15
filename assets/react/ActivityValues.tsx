import {useRef} from "react";
import {useForm, SubmitHandler} from "react-hook-form";

type FormInputs = {
    moveResult: number,
    steps: number,
    distance: number,
    exerciseResult: number,
    standUpResult: number
}

export const ActivityValues = (data: FormInputs, date: string) => {
    const {register, handleSubmit, formState: {errors}} = useForm<FormInputs>();
    const formRef = useRef<HTMLFormElement>(null);
    const resultRef = useRef<HTMLDivElement>(null);

    console.log(data);

    const onSubmit: SubmitHandler<FormInputs> = (data) => {
        console.log(data);

        formRef.current?.reset();
        resultRef.current?.classList.add("show");
    }

    return (
        <div className={"activity-values"}>
            <div className="activity-values-title">{date}</div>

            <div className="activity-values-result" ref={resultRef}>
                <div>Votre message a bien été envoyé.</div>
                <div className="btn btn-primary right" onClick={() => resultRef.current?.classList.remove("show")}>Fermer</div>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} ref={formRef}>
                <div className="activity-values-form">
                    <div className="form-item">
                        <label htmlFor="moveResult">Bouger</label>
                        {errors.moveResult && <span className="error">Ce champ est obligatoire</span>}
                        <input {...register("moveResult", {required: true})} value={data.moveResult} type="number" id="moveResult" name="moveResult" placeholder="Nombre de KCal"/>
                    </div>

                    <div className="form-item">
                        <label htmlFor="steps">Nombre de pas</label>
                        {errors.steps && <span className="error">Ce champ est obligatoire</span>}
                        <input {...register("steps", {required: true})} value={data.steps} type="number" id="steps" name="steps" placeholder="Nombre de pas"/>
                    </div>

                    <div className="form-item">
                        <label htmlFor="distance">Distance</label>
                        {errors.distance && <span className="error">Ce champ est obligatoire</span>}
                        <input {...register("distance", {required: true})} value={data.distance} type="number" id="distance" name="distance" placeholder="Nombre de kilomètre"/>
                    </div>

                    <div className="form-item">
                        <label htmlFor="exerciseResult">Exercice</label>
                        {errors.exerciseResult && <span className="error">Ce champ est obligatoire</span>}
                        <input {...register("exerciseResult", {required: true})} value={data.exerciseResult} type="number" id="exerciseResult" name="exerciseResult" placeholder="Nombre de minutes"></input>
                    </div>
                    <div className="form-item">
                        <button className="btn btn-primary right" type="submit">Mettre à jour</button>
                    </div>
                </div>
            </form>
        </div>
    );
}