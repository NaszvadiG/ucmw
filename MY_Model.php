<?php
/*
** Unified CodeIgniter Model Wrapper
** AUTHOR: Maxim Titovich
** 2015-2016
*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model
{

    protected $CI;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    public function __get($name)
    {
        if(is_string($name))
        {
            $function_name = "load_".$name;
            if(method_exists($this, $function_name))
            {
                $this->{$name} = $this->$function_name();
                return $this->{$name};
            }
        }
    }

    private function getClassName()
    {
        return str_replace('_model', '', strtolower(get_class($this)));
    }

    private function setFilter($filter)
    {
        foreach ($filter as $key => $value)
            if(!is_numeric($key))
                if(is_array($value))
                    $this->CI->db->where_in($key, $value);
                else
                    $this->CI->db->where($key, $value);
            else
                $this->CI->db->where($value);
    }

    private function setOrder($order)
    {
        foreach($order as $key => $value)
            $this->CI->db->order_by($key, strtolower($value) == 'desc'?'desc':'asc');
    }

    private function setFields($customSelect)
    {
        $this->CI->db->select("*, ".implode(", ",$customSelect));
    }

    public function getByField($field = null, $value = null)
    {
        if($field && $value)
        {
            $result = $this->getAll(array($field => $value), null, 1);
            return array_shift($result);
        }
        else
            return null;
    }

    public function getByPk($id = 0, $pk = 'id')
    {
        if(intval($id) > 0)
            return $this->getByField($pk, $id);
        else
            return null;
    }

    public function countAll($filter = null)
    {
        if(count($filter) > 0)
        {
            $this->setFilter($filter);
            return $this->CI->db->count_all_results($this->getClassName());
        }
        else
            return $this->CI->db->count_all($this->getClassName());
    }

    public function getAll($filter = null, $order = null, $limit = null, $offset = null, $customFields = null)
    {
        if(count($filter) > 0)
            $this->setFilter($filter);

        if(count($order) > 0)
            $this->setOrder($order);
        if(count($customFields) > 0)
            $this->setFields($customFields);

        if($limit && $offset)
            $this->CI->db->limit($limit, $offset);
        elseif($limit)
            $this->CI->db->limit($limit);
        elseif($offset)
            $this->CI->db->limit(1000, $offset);

        return $this->CI->db->get($this->getClassName())->custom_result_object(get_class($this));
    }

    public function save($data, $array = null)
    {
        if($array && is_array($array))
		{
            $this->CI->db->update($this->getClassName(), $data, $array);
            $return_data = $this->getAll(array_merge($array, $data));
            if($return_data)
                return array_shift($return_data);
            else
                return null;
		}
        else
        {
            $this->CI->db->insert($this->getClassName(), $data);
            $return_data = $this->getAll($data);
            if($return_data)
                return array_shift($return_data);
            else
                return null;
        }
    }

    public function remove($filter = null)
    {
        if(count($filter) > 0)
        {
            $this->setFilter($filter);
            return $this->CI->db->delete($this->getClassName());
        }
    }

    public function getSessionOrder()
    {
        return $this->CI->session->userdata($this->getClassName().'_order');
    }

    public function setSessionOrder($data)
    {
        return $this->CI->session->set_userdata($this->getClassName().'_order', $data);
    }

    public function getSessionFilter()
    {
        return $this->CI->session->userdata($this->getClassName().'_filter');
    }

    public function setSessionFilter($data)
    {
        return $this->CI->session->set_userdata($this->getClassName().'_filter', $data);
    }

}