<?php namespace FewAgency\FluentHtml;


class IdRegistrar
{
    protected $repository = [];

    public function unique($desired_id)
    {
        if ($this->add($desired_id)) {
            return $desired_id;
        }
        //Isolate the last section of digits
        preg_match('/^(.+?)(\d+)?$/', $desired_id, $matches);
        $base = $matches[1];
        $next_number = max($matches[2], 1) + 1;

        return $this->unique($base . $next_number);
    }

    /**
     * @param $id
     * @return bool true if the id is already used
     */
    public function exists($id)
    {
        return $this->has($id);
    }

    /**
     * Determine if an id exists in the repository
     *
     * @param $id
     * @return bool
     */
    protected function has($id)
    {
        return in_array($id, $this->repository);
    }

    /**
     * Store an id in the repository if it does not exist
     * @param $id string
     * @return bool
     */
    protected function add($id)
    {
        if (!$this->has($id)) {
            $this->repository[] = $id;

            return true;
        }

        return false;
    }
}